<?php
require_once 'config.php';

if (!isset($_COOKIE['class'])) {
    header('Location: login.php');
}
$class = $_COOKIE['class'];

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT capacity FROM class WHERE classname = :class');
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $capacity = $row['capacity'];
} catch (PDOException $e) {
    echo $e->getMessage();
}


?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>定員登録ページ</title>
</head>

<body>
    <h1>定員登録</h1>
    <?php echo $class . "としてログイン中" ?><br>
    <?php echo "今登録されている定員は" . $capacity . "です" ?>

    <form action="add.php" method="POST" id="submit">
        <input type="number" id="capacity" name="capacity" placeholder="半角でお願いします">
        <button type="submit" id="add" onclick="return validateNumber()">追加</button>
    </form>

    <div class="discription">
        <p>定員とは出し物の中に入れる最大グループ数のことです。<br> 一度に二人以上で入場できる場合でも、それは1とカウントしてください。</p>
        <span>例: 出し物がお化け屋敷</span>
        <p>中に入れる最大グループ数は1 →定員は1</p> <br>
        <p>あくまでも例なのでここの数は話し合って決めてください。</p>
    </div>

    <div class="go">
        <a href="index.php">メインページへ</a>
    </div>
    <script>
        function validateNumber() {
            var numberValue = document.getElementById('numberInput').value;

            if (numberValue == 0) {
                alert('数値が0です。送信できません。');
                return false;
            }

            return true;
        }
    </script>
</body>

</html>