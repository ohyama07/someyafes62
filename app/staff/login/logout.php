<?php


// Cookieの削除
setcookie("cookie_class", "cookie_password", time() - 3600, "/"); // 過去の時間を設定

session_start();
unset($_SESSION['userid']);
header('Location: login.php', 303);
session_write_close();