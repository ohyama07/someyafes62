<?php
require_once 'config.php';

if (!isset($_COOKIE['class'])) {
    header('Location: login.php');
    exit;
}
$class = $_COOKIE['class'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ((int)$_POST['capacity'] === 0) {
        echo "0は入力できません";

        echo "<br>3秒後に登録ページに戻ります";
        echo '<script>
        setTimeout(function(){
            window.location.href = "addCapacity.php";
        }, 3000);
        </script>';
        exit;
    }
    $add_capacity = $_POST['capacity'];


    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('UPDATE class SET capacity = :capacity WHERE classname = :class');
        $stmt->bindValue(':capacity', $add_capacity, PDO::PARAM_INT);
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        echo "定員{$add_capacity}で登録しました";
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

echo "<br>3秒後にメインページに戻ります";
echo '<script>
        setTimeout(function(){
            window.location.href = "index.php";
        }, 3000);
        </script>';