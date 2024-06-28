<?php

// JSONの受け取り
$input = file_get_contents('php://input');
$data = json_decode($input, true);
setcookie("png", $data, time() + 86400, "/");

// pngデータをセッションに保存
if (isset($data['png'])) {
    $_COOKIE['png'] = $data['png'];
    echo $_COOKIE['png'];
} else {
    header('Location: index.php');
    exit;
}

