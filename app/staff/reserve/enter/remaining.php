<?php
function inRemaining($class)
{
    require 'config.php';

    //$class = $_COOKIE['class'];

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //許容範囲計算
        // classテーブルからそのクラスの定員を取得する
        $stmt = $pdo->prepare('SELECT capacity FROM class WHERE classname = :class');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo "定員を取得できませんでした";
            return;
        }
        $capacity = $row['capacity'];
        //何したいのかわからない
        $expdo = "SELECT enters.count - permits.count FROM (SELECT COUNT(*)AS count FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NULL) AS enters INNER JOIN (SELECT COUNT(*)AS count FROM queue WHERE class = :class AND enter IS NOT NULL AND permit IS NULL) AS permits ON enters.class = permits.class";
        //permitが出場時だと入れるのには入れない時間が発生する。さっきは作業手順逆にしたからpermitが付与されない人が出る事案が発生したけど、出場をしなかった人がいたら0が付与されるまで絶対にpermitが1減った状態になる

        // 入場している数をカウントする　permitも参照しなきゃいけない
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NULL');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo "値がありません";
                return;
            }
            $count = $row['count'];
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }
        
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM queue WHERE permit IS NOT NULL AND enter IS NULL AND class = :class');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo "値がありません";
                return;
            }
            $permit_count = $row['count'];//入場が許可されている人のカウント
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }

        
        // 定員の値とカウントした値を引き算する
        $remaining = $capacity - $count - $permit_count;
        if ($remaining < 0) {
            $remaining = 0 - $remaining;
            echo "入場数が{$remaining}人超過しています。定員を追加してください。<br>";
            return 0;
        }
        return $remaining;
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }
}
/*   
 // queueテーブルからそのユーザーのクラスを取得する
$stmt = $pdo->prepare("SELECT class FROM queue WHERE userid = :userid");
$stmt->bindValue(':userid', $userid, PDO::PARAM_STR); // bindValueの第二引数に$useridを追加
$stmt->execute(); // execute()を追加
$class = $stmt->fetch(PDO::FETCH_ASSOC)['class'];
if ($class === false) {
echo "クラスを取得できませんでした";
return;
}
*/