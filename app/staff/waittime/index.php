<?php
require 'config.php';
include 'waittime_cal.php';


session_start();
$class = $_SESSION['class'];
session_write_close();



try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, enter, start)) AS avg_waitingtime, AVG(TIMESTAMPDIFF(SECOND, leaving, enter)) AS avg_stayingtime FROM queue WHERE enter <> 0 AND leaving <> 0 AND leaving IS NOT NULL");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $avg_waitingtime = $result[0]['avg_waitingtime']; //待ち時間
    $stayingtime = $result[0]['avg_stayingtime']; //滞在時間

}catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}


    $stmt = $pdo->prepare("SELECT COUNT(*) AS COUNT FROM queue WHERE enter IS NULL AND class = :class;");//待ち行列の長さをとる
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $waiting = $row['COUNT'];

    $stmt = $pdo->prepare("SELECT capacity FROM class WHERE classname = :class");//定員を取る
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $capacity = $stmt->fetch(PDO::FETCH_ASSOC);



//定員＜＝待ち人数の時はavg_waitingtime＋先頭のenterー現在時刻で先頭で並んでいる人の予想滞在時間がわかる
//それ以外の時はavg_waitingtime / capacity * 待ち人数で予測する
//MEMO 全体の待ち時間とその人の待ち時間も欲しいかもな



//FIXME? ここ要る？　echo waittimeCal($class, $userid);