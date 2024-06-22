<?php
require 'config.php';
include 'remaining.php';
include 'limited_records.php';
include '../../waittime/index.php';

$already = true;//MEMO 既にカラムがありますよってこと

if (!isset($_COOKIE['class'])) {
    header('Location: ../../login/login.php');
    exit;
}
$class = $_COOKIE['class'];
// POSTリクエストからuseridを取得し、存在しない場合はエラーメッセージを表示
if (!isset($_POST['userid'])) {
    echo "IDが見つかりません";
    echo '<script>
    setTimeout(function(){
        window.location.href = "enter.html";
    }, 1500);
    </script>';
    exit;
} elseif ($_POST['userid'] === "00000000000000000") {
    echo "idを正しく入力してください";
    echo '<script>
    setTimeout(function(){
        window.location.href = "enter.html";
    }, 1500);
    </script>';
    exit;
} else {
$userid = $_POST['userid'];
}

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $remaining = inRemaining($class);
    $limits = inLimited($pdo, $class);
    $stmt = $pdo->prepare('SELECT class FROM queue WHERE userid = :userid AND class <> :class AND enter IS NULL');
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue('class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        //浮気したやつ(のpermit)を消す処理
        //1,useridが一致しない、入場処理したクラスとは違うクラスの昇順上からremaining番目まで(今回は2番目)を取ってきてpermitを代入
        $stmt = $pdo->prepare('UPDATE queue SET permit = NOW() WHERE id IN (SELECT id FROM (SELECT id FROM queue WHERE class <> :class AND enter IS NULL AND permit IS NULL AND userid <> :userid ORDER BY start ASC LIMIT :remaining)AS subquery)');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->bindValue(':remaining', $remaining, PDO::PARAM_INT);
        $stmt->execute();

        //2,useridが他と浮気したやつと一致しないかつpermit付与対象でないやつのpermitをNULLにする→permit付与対象を適切にする LIMIT100は大きい値を入れただけ
        //1,2の処理で浮気したやつの次の人にpermitを付与させることを意味する(コンパクトにする方法分からなかったからこんな冗長になってる)
        $stmt = $pdo->prepare('UPDATE queue SET permit = NULL WHERE id IN (SELECT id FROM (SELECT id FROM queue WHERE enter IS NULL AND permit IS NOT NULL AND userid <> :userid ORDER BY start ASC LIMIT 100 OFFSET 2) AS subquery);'); //FIXME OFFSET の後を2じゃなくて適切な値に変更
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->execute();

        $stmt = $pdo->prepare('UPDATE queue SET permit = NULL WHERE userid = :userid AND class <> :class AND permit IS NOT NULL AND enter IS NULL');
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        //3,他クラスに浮気したやつのpermitを削除する
    }

} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND class = :class AND leaving IS NULL");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}



//③の時
foreach ($result as $row) {
    if (!empty($row) && $row['enter'] !== false) {
        $stmt = $pdo->prepare("UPDATE queue SET leaving = '1970-01-01 00:00:00' WHERE userid = :userid AND enter IS NOT NULL AND leaving IS NULL");
        $stmt->bindValue(':userid', $row['userid'], PDO::PARAM_STR);
        $stmt->execute();
    }
}
$stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND class = :class");
$stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
$stmt->bindValue(':class', $class, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);


//②以外のとき　→enter IS NULL
try {
    $stmt = $pdo->prepare("SELECT enter, start FROM queue WHERE userid = :userid AND class = :class AND enter IS NOT NULL AND start IS NULL");
    $stmt->bindValue(':userid', $userid);
    $stmt->bindValue(':class', $class);
    $stmt->execute();

    //そのユーザーのenter IS NULLのレコードがない場合、新しいレコードを追加
    $stmt = $pdo->prepare("SELECT COUNT(*) AS COUNT FROM queue WHERE userid = :userid AND enter IS NULL");
    $stmt->bindValue('userid', $userid, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($row['COUNT'] === 0) {
        $stmt = $pdo->prepare("INSERT INTO queue (userid, class) VALUES (:userid, :class)");
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $already = false;//ADD 13日
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}


$stmt = $pdo->prepare('SELECT userid FROM queue WHERE class = :class AND start IS NOT NULL AND enter IS NULL ORDER BY start ASC LIMIT :remaining');
$stmt->bindValue(':class', $class, PDO::PARAM_STR);
$stmt->bindValue(':remaining', $remaining, PDO::PARAM_INT);
$stmt->execute();
$limits_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
$limits_count = count($limits_ids);

$can_enter = false;
$over_permit = 0;

if ($remaining > 0) {   //許容範囲計算した後の判定
    $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM queue WHERE class = :class AND enter IS NULL AND permit < NOW() - INTERVAL 5 MINUTE ');//スキップ対象計算
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $permit_count = $row['count'];
    $limits_count += $permit_count;
    var_dump($remaining, $limits_count);
    foreach ($limits_ids as $limit_id) {   //前から許容人数番目(=$limits_count)までにいるかの判定
        /*$sql = "UPDATE queue SET permit = NOW() WHERE permit IS NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();*/
        if (!empty($limit_id) && $limit_id['userid'] === $userid) {
            $can_enter = true;
            break;
        }
    }
}
//FIXME permitの代入を適切な方法に変更しております。
//FIXME 他のクラスだとどっかが動かないから入場させてくれない


if ($can_enter) {
    $stmt = $pdo->prepare("UPDATE queue SET enter = NOW() WHERE userid = :userid AND class = :class AND enter IS NULL");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    echo "入場してください";
} elseif ($already) {//$already = true ってことは予約処理済かつ入場できないってこと
    echo "まだ入場できません  もう少しお待ちください";//ADD 13日
    echo "<br>";
    echo waittimeCal($class, $userid);
} else {
    echo "予約しました <br>";
    echo waittimeCal($class, $userid);
}
/*
echo "<br>3秒後に元のページに戻ります";
echo '<script>
        setTimeout(function(){
            window.location.href = "enter.html";
        }, 3000);
        </script>';*///FIXME あとで

//一般公開終了間近になったら一般客を優先させる処理
try {
    $stmt = $pdo->prepare('SELECT * FROM queue WHERE class = :class AND enter IS NULL 
                                ORDER BY CASE WHEN LEFT(userid , 1) = "0" THEN 0 ELSE 1 END , start ASC');
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    echo $e->getMessage();
}//このイベントのトリガーは何？➡設定画面から変更


//MEMO 出てこない人がいたら定員を一時的に手動で増やせるようにすればいい→別ページに乗っける(noticeはいらないかな)　別にフィールドを設けてプラスマイナスをわかりやすくさせることにする




/*
    // 許容範囲計算
    $remaining = include 'remaining.php';

    // 出し物の定員未満ならenterにもstartと同じ値を入れる処理を追加する
    if ($remaining !== 0) {

    //前から$remaining番目にあるかどうか調べる
    $records = getLimitedRecords($pdo, $remaining);
    if (in_array($userid, array_column($records, 'userid'))) {
        $stmt = $pdo->prepare("UPDATE queue SET start = NOW() WHERE userid = :userid");
        $stmt->execute(['userid' => $userid]);

        // queueテーブルのenterに現在のタイムスタンプを挿入して入場させる
        $stmt = $pdo->prepare("UPDATE queue SET enter = NOW() WHERE userid = :userid");
        $stmt->execute(['userid' => $userid]);
        echo "入場してください";
        exit;

    } else {
        //permit IS null && タイムスタンプが一番小さいかどうかを調べる　→来ない人がいては入れないだけなのかを確認する
        $stmt = $pdo->prepare("SELECT * FROM queue WHERE permit IS NULL ORDER BY start ASC LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            //startに現在のタイムスタンプを入れる
            $stmt = $pdo->prepare("UPDATE queue SET start = NOW() WHERE userid = :userid");
            $stmt->execute(['userid' => $userid]);

            //permitに0を入れる
            $stmt = $pdo->prepare("UPDATE queue SET permit = 0 WHERE userid = :userid");
            $stmt->execute(['userid' => $userid]);

            //入場させる
            $stmt = $pdo->prepare("UPDATE queue SET enter = NOW() WHERE userid = :userid");
            $stmt->execute(['userid' => $userid]);
            echo "入場してください";
            exit;
        } else {
             // 特定のユーザーIDでstartがNULLのレコードを検索
        $stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND start IS NULL");
        $stmt->execute(['userid' => $userid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($row) {
            // startがNULLの場合、startに現在のタイムスタンプを設定
            $stmt = $pdo->prepare("UPDATE queue SET start = NOW() WHERE userid = :userid");
            $stmt->execute(['userid' => $userid]);
            echo "予約しました";
    
            // 特定のユーザーIDで、異なるクラスに属し、enterがNULLでstartがNULLでないレコードを検索
            $stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND class != :class AND enter IS NULL AND start IS NOT NULL");
            $stmt->execute(['userid' => $userid, 'class' => $class]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($row) {
                // 条件に合致するレコードが存在する場合、enterとleavingを'00:00:00'に更新
                $stmt = $pdo->prepare("UPDATE queue SET enter = '00:00:00', leaving = '00:00:00' WHERE userid = :userid AND class = :class");
                $stmt->execute(['userid' => $userid, 'class' => $row['class']]);
            } else {
            echo "入場できません　再度時間を空けて処理をしてください";
        }
        exit;
        }
    }
}
    }
*/
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
</head>

<body>
</body>

</html>