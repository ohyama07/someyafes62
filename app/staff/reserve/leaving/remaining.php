<?php
function inRemaining(){
require 'config.php';
/*
session_start();
$class = $_SESSION['class'];  //class = 1年1組など…
*/

$class = '3年1組';

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
    

    // 入場している数をカウントする
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NULL");
    $stmt->bindValue(':class', $class, PDO::PARAM_STR); 
    $stmt->execute(); // execute()を追加
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($count === false) {
        echo "値がありません";
        return;
    }
    // 定員の値とカウントした値を引き算する
    $remaining = $capacity - $count;
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