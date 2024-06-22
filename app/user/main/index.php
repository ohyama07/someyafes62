<?php
include 'config.php';

// userid クッキーがセットされているか確認
if (isset($_COOKIE['userid'])) {
    $userid = $_COOKIE['userid'];
    echo "userid クッキーの値は {$userid} です。";
} else {
    echo "userid クッキーはセットされていません。";
}

// seeable_id クッキーがセットされているか確認
if (isset($_COOKIE['seeable_id'])) {
    $seeable_id = $_COOKIE['seeable_id'];
    echo "seeable_id クッキーの値は {$seeable_id} です。";
} else {
    echo "seeable_id クッキーはセットされていません。";
}




if (!isset($_COOKIE['userid'])) {
    //header('Location: index.php');
    //exit;
}
$userid = $_COOKIE['userid'];
$waittime;
$time = time();

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT class FROM queue WHERE enter IS NULL AND userid = :userid');
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $class = "まだ入場していません";
        $waittime = "まだ入場していません";
    } else {
        $class = $row['class'];
        $stmt = $pdo->prepare('SELECT expecttime FROM class WHERE classname = :class');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $exwaittime = "まだ入場していません";
        } else {
            $waittime = (int) $row['expecttime'] + $time;
            $waittime = date('H:i', $waittime);
        }

    }


} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}


?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メイン画面</title>
    <style>
        body {
            text-align: center;
        }

        #newImage {
            display: none;
            /* 初期状態で非表示 */
            max-width: 100%;
            /* QRコードサイズを制限 */
            height: auto;
            margin: 0 auto;
        }

        #imageSelect {
            margin-bottom: 3em;
        }
    </style>
</head>

<body>
    <h1>ユーザーメイン</h1>
    <p><?php
    echo "予約中のクラス" . $class . "<br>予想入場時刻" . $waittime;
    ?></p>
    <div id="qrcode">
        <select id="imageSelect">
            <option value="show">QRコードを表示</option>
            <option value="hide">QRコードを非表示</option>
        </select>
        <div><canvas id="newImage" alt="QRコード"></div>
    </div>
    <div class="otherpage">
        <a href=""></a>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const imageSelect = document.getElementById('imageSelect');
            const newImage = document.getElementById('newImage');
            const userid = "<?php echo $userid ?>"; // QRコードに変換したい文字列

            // QRコード生成
            const qr = new QRious({
                element: newImage,
                value: userid,
                background: '#FFF',
                backgroundAlpha: 0.8,
                foreground: '#000000',
                foregroundAlpha: 1.0,
                level: 'L',
                size: 240
            });

            // QRコードをQRコードに変換
            const png = newImage.toDataURL();
            newImage.src = png;
            newImage.style.display = 'block';

            imageSelect.addEventListener('change', (event) => {
                const value = event.target.value;
                if (value === 'show') {
                    newImage.style.display = 'block';
                } else {
                    newImage.style.display = 'none';
                }
            });
        });
    </script>
    <a href="waitlist.php">待ち時間一覧表示ページへ</a>
</body>

</html>