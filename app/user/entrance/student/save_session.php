<?php
session_start();

// JSONの受け取り
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// pngデータをセッションに保存
if (isset($data['png'])) {
    $_SESSION['userid'] = $data['png'];
    echo $_SESSION['userid'];
} else {
    header('Location: index.php');
}

session_write_close();
