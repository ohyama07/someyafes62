<?php
require_once 'config.php';


$class = $_COOKIE['class'];


try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('UPDATE class SET capacity = capacity + 1 WHERE classname = :class');
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}
echo "定員を1追加しました";

echo "<br>3秒後に元のページに戻ります";
echo '<script>
        setTimeout(function(){
            window.location.href = "enter.php";
        }, 3000);
        </script>';

