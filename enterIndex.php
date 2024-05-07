<?php
require_once 'config.php';
$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
$userid = $_SESSION['userid'];
$class = $_SESSION['username'];
*/
$userid = "0000";
$class = "3年1組";//暫定的なもの

    // 関数で定義しているから$remaining = getRemainingCapacity($pdo, $class);とやればいつでも呼び出せる
    function getRemainingCapacity($pdo, $userid) {
        // queueテーブルからそのユーザーのクラスを取得する
        $stmt = $pdo->prepare("SELECT class FROM queue WHERE userid = :userid");
        $stmt->execute(['userid' => $userid]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC)['class'];
        if ($class === false) {
            echo "クラスを取得できませんでした";
            return;
        }
        // classテーブルからそのクラスの定員を取得する
        $stmt = $pdo->prepare("SELECT capacity FROM class WHERE class = :class");
        $stmt->execute(['class' => $class]);
        $capacity = $stmt->fetch(PDO::FETCH_ASSOC)['capacity'];
        if ($capacity === false) {
            echo "定員を取得できませんでした";
            return;
        }
        // start IS NOT NULL && leaving IS NULL　をカウントする
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enter WHERE start IS NOT NULL AND leaving IS NULL");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($count === false) {
            echo "値がありません";
            return;
        }
        // 定員の値とカウントした値を引き算する
        return $capacity - $count;
    }
    
    //前から許容人数番目までを計算する関数
    function getLimitedRecords($pdo, $remaining) {
        $stmt = $pdo->prepare("SELECT * FROM enter WHERE start IS NOT NULL AND leaving IS NULL ORDER BY start ASC LIMIT :remaining");
        $stmt->bindValue(':remaining', $remaining, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

// ①そのuserに関するレコードがない(初入場)
$stmt = $pdo->prepare("SELECT class FROM queue WHERE userid = :userid");
$stmt->execute(['userid' => $userid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// ②startにレコードがある
$stmt = $pdo->prepare("SELECT enter FROM queue WHERE userid = :userid");
$stmt->execute(['userid' => $userid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// ③のとき
// leavingに0を入れ、①へ 
$stmt = $pdo->prepare("UPDATE queue SET leaving = '00:00:00' WHERE userid = :userid AND enter IS NOT NULL AND leaving IS NULL");
$stmt->execute(['userid' => $userid]);

// ④startとenterとleaving(全て)にレコードがある(再入場)
$stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND start IS NOT NULL AND enter IS NOT NULL AND leaving IS NOT NULL");
$stmt->execute(['userid' => $userid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || ($row['start'] !== NULL && $row['enter'] !== NULL && $row['leaving'] !== NULL)) {
    //新しいレコードを追加
    $stmt = $pdo->prepare("INSERT INTO queue (userid) VALUES (:userid)");
    $stmt->execute(['userid' => $userid]);
    exit;
}
    // 出し物の定員と現在の入場者数を参照する処理 ➡許容範囲計算ってことよね
    $remaining = getRemainingCapacity($pdo, $class);

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
            }
        } else {
            echo "入場できません　再度時間を空けて処理をしてください";
        }
        }
    }
    }


?>