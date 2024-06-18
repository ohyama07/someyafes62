<?php
require_once 'config.php';
include 'remaining.php';

/*
$class = $_SESSION['username'];
*/
// POSTリクエストからuseridを取得し、存在しない場合はエラーメッセージを表示
if (!isset($_POST['userid'])) {
    echo "ユーザーIDが未定義です。";
    exit;
}
$userid = $_POST['userid'];
$class = "3年1組";//暫定的なもの

//MEMO 人が確認して値を入れられるようにするのもいいね

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND class = :class AND leaving IS NULL");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);//classはclassテーブルからIDを取得して入れることにするほうが早いかな
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare('SELECT enter FROM queue WHERE enter IS NULL AND userid = :userid AND class = :class');
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $nullEnter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($result)) {
        echo "予約処理をしてください こちらは出場処理です";
    } elseif (!empty($nullEnter)) {
        echo "入場処理をしてください こちらは出場処理です";
    } else {
        //出場処理
        $stmt = $pdo->prepare("UPDATE queue SET leaving = now() WHERE userid = :userid AND class = :class AND leaving IS NULL AND enter IS NOT NULL");
        $stmt->bindValue(':userid', $userid);
        $stmt->bindValue(':class', $class);
        $stmt->execute();
        echo "ありがとうございました";
    }

} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

try {//ADD 13日
    $remaining = inRemaining();
    $stmt = $pdo->prepare('UPDATE queue SET permit = NOW() WHERE class = :class AND permit IS NULL AND enter IS NULL ORDER BY start ASC LIMIT :remaining');
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->bindValue(':remaining', $remaining, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}
echo "<br>3秒後に元のページに戻ります";
echo '<script>
        setTimeout(function(){
            window.location.href = "http://localhost/someyasai/app/reserve/leaving/leaving.html";
        }, 3000);
        </script>';//FIXME 本番とは違うアドレス注意
