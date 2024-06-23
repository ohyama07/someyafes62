<?php
require_once 'config.php';
$err = '';

if (isset($_COOKIE['class'])) {
    header('Location: index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = $_POST['userid'];
    $user_password = $_POST['password'];

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE userid = :class');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }


    if (!$row) {

        $err = "ユーザーが見つかりません";

    } elseif ($row['password'] !== $user_password) {

        $err = "パスワードが違います";

    } else {
        setcookie("class", $row['username'], time() + 86400); // 有効期限は1日
        header('Location: index.php');
        exit;

    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <style>
        /* 全体の中央揃え */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            height: 100vh;
            margin: 0;
        }

        /* 中央に配置されるフォーム */
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f5f5f5;
            padding: 2em;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* テキスト要素のスタイル */
        .texts {
            width: 100%;
        }

        /* input要素のスタイル */
        input {
            width: 100%;
            max-width: 300px;
            /* テキストフィールドの最大幅 */
            height: 2em;
            padding: 8px;
            box-sizing: border-box;
            margin-bottom: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* ボタンのスタイル */
        #send {
            width: 100%;
            max-width: 300px;
            /* ボタンの最大幅 */
            height: 2.5em;
            border: none;
            background-color: #4CAF50;
            color: white;
            font-size: 1em;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        #send:hover {
            background-color: #45a070;
        }
    </style>
</head>

<body>
    <form action="login.php" method="POST" id="submit">
        <h1>ログインページ</h1>
        <?php if ($err) { ?>
            <p class="error"><?= $err ?></p>
        <?php } ?>
        <div class="texts">
            <input type="text" id="userid" name="userid" placeholder="クラス">
            <br>
            <input type="text" id="password" name="password" placeholder="パスワード">
        </div>
        <input type="submit" id="send" name="send" value="ログイン">
    </form>
</body>

</html>