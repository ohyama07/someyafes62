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
    <title>2-4特設サイト</title>
</head>

<body>
    <p id="message"></p>
    <a href="../twofour/enter.html">予約・入場処理ページへ</a>
    <br>
    <a href="../twofour/leaving.html">出場処理ページへ</a>
    <br>
    <a href="addCapacity.php">定員を確認・更新する</a>
    <br>
    <a href="logout.php">ログアウトしてログインページへ</a>
    <script>
        let message = document.querySelector("#message");
        message.textContent = "<?php echo $_COOKIE['class'] . 'としてログイン中'; ?>";
    </script>
</body>

</html>