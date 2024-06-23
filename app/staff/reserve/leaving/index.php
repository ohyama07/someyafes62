<?php
require_once 'config.php';
include 'remaining.php';

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

    // 入場処理がされていないデータの取得
    $stmt = $pdo->prepare("SELECT enter FROM queue WHERE enter IS NULL AND userid = :userid AND class = :class");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $nullEnter = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        echo '<script>
        setTimeout(function(){
            window.location.href = "leaving.php";
        }, 1500);
        </script>';
    }

    // 残りの処理
    $remaining = inRemaining($class);
    $stmt = $pdo->prepare('UPDATE queue SET permit = NOW() WHERE class = :class AND permit IS NULL AND enter IS NULL ORDER BY start ASC LIMIT ' . (int)$remaining);
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
    exit;
}

echo "<br>3秒後に元のページに戻ります";
echo '<script>
        setTimeout(function(){
            window.location.href = "leaving.php";
        }, 3000);
        </script>';
