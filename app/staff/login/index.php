<?php
require_once 'config.php';

if (!isset($_COOKIE['class'])) {
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフページ</title>
</head>

<body>
    <p id="message"></p>
    <a href="../reserve/enter/enter.php">予約・入場処理ページへ</a>
    <br>
    <a href="../reserve/leaving/leaving.php">出場処理ページへ</a>
    <br>
    <a href="logout.php">ログアウトしてログインページへ</a>
    <script>
        let message = document.querySelector("#message");
        message.textContent = "<?php echo $_COOKIE['class'] . 'としてログイン中'; ?>"
    </script>
</body>

</html>