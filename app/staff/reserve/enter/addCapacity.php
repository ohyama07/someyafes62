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

