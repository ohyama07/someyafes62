<?php
function makingQrcode() {
require_once 'config.php';



$today = strtotime("now");
$today = dechex($today);
$hash = hash('CRC32', $today);
$userid = '0'.hash('CRC32', $today . $hash) . $today;

session_start();
$_SESSION['userid'] = $userid;
session_write_close();


try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("INSERT INTO usersid (userid) VALUES (:userid)");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->execute();

} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM usersid WHERE userid = :userid");
$stmt->bindValue(':userid', $userid);
$stmt->execute();
$seeable_id = $stmt->fetch(PDO::FETCH_ASSOC);
}