<?php
require_once 'config.php';
$reserve = false;
$enter = false;
$leaving = false;

if (!isset($_COOKIE['class']) || $_COOKIE['class'] !== "2年4組") {
    header('Location: ../login/login.php');
} else {
    $class = $_COOKIE['class'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $post = $_POST['action'];
        if ($post === 'reserve') {
            $reserve = true;
        } elseif ($post === 'enter') {
            $enter = true;
        } elseif ($post === 'leaving') {
            $leaving = true;
        } else {
            echo "上手く送信できませんでした。";
            echo "<br>3秒後に元のページに戻ります";
            echo '<script>
        setTimeout(function(){
            window.location.href = "enter.html";
        }, 3000);
        </script>';
        }
    } else {
        echo "送信されませんでした";
        echo "<br>3秒後に元のページに戻ります";
        echo '<script>
        setTimeout(function(){
            window.location.href = "enter.html";
        }, 3000);
        </script>';
    }
}


$waits = 0;
$avg_waitingtime = 0;
$enter_time = 0;
$leaving_time = 0;
$expect_waittime = 0;

if ($reserve) {
    $waits++;

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('SELECT AVG(TIMESTAMPDIFF(SECOND, enter,leaving)) AS avg_waitingtime FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NOT NULL AND leaving <> 0 AND leaving - enter <= 900');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $avg_waitingtime = $row['avg_waitingtime'];
        } else {
            $avg_waitingtime = 0;

        }
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }

    $expect_waittime = $avg_waitingtime * $waits / 60;
    $expect_waittime = round($expect_waittime);

    try {
        $stmt = $pdo->prepare('INSERT INTO queue (userid, start, class) VALUE ("twofour", NOW(), :class)');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }

    try {
        $stmt = $pdo->prepare('UPDATE class SET expecttime = :expect WHERE classname = :class');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->bindValue(':expect', $expect_waittime, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }

    $reserve = false;
    echo "正常に予約処理をしました";
    echo "<br>3秒後にメインページに戻ります";
    echo '<script>
        setTimeout(function(){
            window.location.href = "enter.html";
        }, 3000);
        </script>';

} else if ($enter) {
    if ($waits !== 0) {
        $waits--;
    }

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT enter FROM queue WHERE enter IS NOT NULL AND leaving IS NULL AND class = :class');//入場しているところがあったら、入場させない処理
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "既に入場しています";
            echo "<br>3秒後にメインページに戻ります";
            echo '<script>
        setTimeout(function(){
            window.location.href = "enter.html";
        }, 3000);
        </script>';
            exit;
        }


        $stmt = $pdo->prepare('SELECT * FROM queue WHERE class = :class AND userid = "twofour" AND start IS NOT NULL AND enter IS NULL');//予約中があるかどうか
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {  //予約中があったら
            $stmt = $pdo->prepare('UPDATE queue SET enter = NOW() WHERE class = :class AND enter IS NULL');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->execute();
        } else { //なかったら
            $stmt = $pdo->prepare('INSERT INTO queue (userid, class, start, enter) VALUE ("twofour", :class, NOW(), NOW())');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }

    $enter = false;
    echo "正常に入場処理をしました";
    echo "<br>3秒後にメインページに戻ります";
    echo '<script>
        setTimeout(function(){
            window.location.href = "enter.html";
        }, 3000);
        </script>';

} elseif ($leaving) {

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('SELECT enter FROM queue WHERE enter IS NULL AND start IS NOT NULL AND class = :class');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $stmt = $pdo->prepare('UPDATE queue SET leaving = NOW() WHERE class = :class AND leaving IS NULL AND enter IS NOT NULL');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            echo "出場すべき人がいません";
            echo "<br>3秒後にメインページに戻ります";
            echo '<script>
        setTimeout(function(){
            window.location.href = "leaving.html";
        }, 3000);
        </script>';
            exit;
        }

    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }
    $leaving = false;
    echo "正常に入場処理しました";
    echo "<br>3秒後にメインページに戻ります";
    echo '<script>
        setTimeout(function(){
            window.location.href = "leaving.html";
        }, 3000);
        </script>';
}

