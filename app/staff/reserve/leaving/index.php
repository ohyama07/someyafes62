<?php
require_once 'config.php';
include 'remaining.php';

if (!isset($_COOKIE['class'])) {
    header('Location: ../../login/login.php');
    exit;
}
$class = $_COOKIE['class'];
$sign = false;

// POSTリクエストからuseridを取得し、存在しない場合はエラーメッセージを表示
if (!isset($_POST['userid'])) {
    echo "IDが見つかりません";
    echo '<script>
    setTimeout(function(){
        window.location.href = "leaving.php";
    }, 1500);
    </script>';
    exit;
} elseif ($_POST['userid'] === "00000000000000000") {
    echo "idを正しく入力してください";
    echo '<script>
    setTimeout(function(){
        window.location.href = "leaving.php";
    }, 1500);
    </script>';
    exit;
} else {
    $userid = $_POST['userid'];
}

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 入場しているが退出していないデータの取得
    $stmt = $pdo->prepare("SELECT * FROM queue WHERE userid = :userid AND class = :class AND leaving IS NULL");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    /*foreach($rows as $row){
        $result = $row['userid'];
    }*/

    // 入場処理がされていないデータの取得
    $stmt = $pdo->prepare("SELECT enter, userid FROM queue WHERE enter IS NULL AND userid = :userid AND class = :class");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $nullEnter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    /*foreach ($rows as $row ){
        $nullEnter = $row['userid'];
    }*/

    if (empty($result)) {
        echo "予約処理をしてください こちらは出場処理です";
        echo '<script>
        setTimeout(function(){
            window.location.href = "leaving.php";
        }, 1500);
        </script>';
        exit;
    } elseif (!empty($nullEnter)) {
        echo "入場処理をしてください こちらは出場処理です";
        echo '<script>
        setTimeout(function(){
            window.location.href = "leaving.php";
        }, 1500);
        </script>';
        exit;
    } else {
        // 出場処理
        $stmt = $pdo->prepare("UPDATE queue SET leaving = NOW() WHERE userid = :userid AND class = :class AND leaving IS NULL AND enter IS NOT NULL");
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        echo "ありがとうございました";
        $sign = true;
    }

    // 残りの処理
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

    $remaining = inRemaining($class) - $permit_count;

    $stmt = $pdo->prepare('UPDATE queue SET permit = NOW() WHERE class = :class AND permit IS NULL AND enter IS NULL ORDER BY start ASC LIMIT ' . (int) $remaining);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
    exit;
}
if($sign) {
    $imagePath = "marusign.png";
    $imageAlt = "まる。";
}

echo "<br>3秒後に元のページに戻ります";

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($imageAlt); ?>"
        style="max-width:100%; height:auto; opacity: 0.7;">

    <script>
        setTimeout(function () {
            window.location.href = "leaving.php";
        }, 3000);
    </script>

</body>

</html>