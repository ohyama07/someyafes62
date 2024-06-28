<?php
// Cookieの削除
setcookie("class", "", time() - 3600, "/"); // 過去の時間を設定
header('Location: login.php', 303);
exit;