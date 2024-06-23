<?php
require_once 'config.php';

function toEntranceId()
{

    global $dsn, $user, $password;
    $today = (int) (new DateTimeImmutable())->format('YmdHisu');
    $today_hex = dechex($today); // Unixタイムスタンプを16進数文字列に変換
    $salt = rand(1, 65535); // ランダムなソルトを生成

    // ハッシュ値を計算するためのデータを準備
    $data = $today_hex . $salt;

    // SHA-256でハッシュ値を計算
    $hash = hash('sha256', $data);

    // CRC32でチェックサムを計算
    $crc32 = crc32($data);

    // ユーザーIDを生成
    $userid = '0' . sprintf("%08x", $crc32) . $hash; // CRC32を16進数文字列に変換して連結


    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("INSERT INTO usersid (userid) VALUES (:userid)");
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->execute();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "同じ値を入れようとしています。再度IDを生成してください";
            exit;
        } else {
            echo "Other error: " . $e->getMessage();
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM usersid WHERE userid = :userid");
        $stmt->bindValue(':userid', $userid);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $seeable_id = $row['id'];
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    return [$seeable_id, $userid];


}
