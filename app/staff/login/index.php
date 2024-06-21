<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['class'])) {
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
    <a href="../reserve/enter/enter.html">予約・入場処理ページへ</a>
    <br>
    <a href="../reserve/leaving/leaving.html">出場処理ページへ</a>
    <br>
    <a href="logout.php">ログアウトしてログインページへ</a>
    <script>
        let message = document.querySelector("#message");
        message.textContent = "<?php echo $_SESSION['class'] . 'としてログイン中'; ?>"
    </script>
</body>

</html>