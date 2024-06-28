<?php
require_once 'config.php';
include '../../staff/waittime/waittime_cal.php';

if (isset($_COOKIE['userid']) && isset($_COOKIE['seeable_id'])) {
    $userid = $_COOKIE['userid'];
    $seeable_id = $_COOKIE['seeable_id'];
} else {
    header('Location: ../entrance/costomer/index.php');
    exit;
}



$waittime = 0;
$time = time();

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT class FROM queue WHERE userid = :userid AND start IS NOT NULL AND enter IS NULL AND leaving IS NULL');
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {       //MEMO 入場しているクラスがなかったらまだ入場していないと表示 入場していたら入場中と表示
        try {
            $stmt = $pdo->prepare('SELECT * FROM queue WHERE enter IS NOT NULL AND leaving IS NULL AND userid = :userid');
            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $enter = "入場中です。";
            } else {
                $enter = "まだ入場していません";
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }
        $waittime = "入場見込み時刻  --:--";
    } elseif (count($rows) === 1) { //MEMO 1箇所だけ予約していたら予約中のクラスを表示
        foreach ($rows as $row) {
            $class = $row['class'];
        }

        $data = waittimeCal($class, $userid);
        $enter = "予約中のクラス:" . $class;
        $waittime = $data['result'];

        try {
            $stmt = $pdo->prepare('SELECT permit FROM queue WHERE enter IS NULL AND permit IS NOT NULL AND class = :class AND userid = :userid');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $enter = "入場できます。該当クラスに行って入場して下さい";
                $waittime = "入場見込み時刻  --:--";
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }

    } elseif (count($rows) >= 2) {  //MEMO 二か所以上あったら(多分到達しないが、)最新のクラスのみを表示
        $stmt = $pdo->prepare('SELECT class, start FROM queue WHERE userid = :userid AND enter IS NULL ORDER BY start DESC LIMIT 1');
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $data = waittimeCal($row['class'], $userid);
            $enter = "予約中のクラス:" . $class;
            $waittime = $data['result'];
        }
    }


} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM queue WHERE enter IS NOT NULL AND leaving IS NULL AND leaving <> 0 AND userid = :userid');
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $enter = "入場中です";
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans JP', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin: 0;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        h1 {
            margin-top: 20px;
        }

        .message {
            display: flex;
            justify-content: center;
            border: 1px solid black;
            box-shadow: 1px 2px 3px black;
            margin: 20px 0;
            padding: 10px;
            width: 80%;
        }

        .container {
            display: flex;
            justify-content: center;
            /* 真ん中に配置 */
            align-items: center;
            width: 80%;
            max-width: 800px;
        }

        .qrcode-container {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        #newImage {
            max-width: 100%;
            height: auto;
        }

        #imageSelect {
            margin-bottom: 1em;
            background-color: white;
            border: 1px solid black;
            box-shadow: 1px 1px 1px black;
        }

        #go {
            background-color: white;
            border: 1px solid black;
            box-shadow: 1px 2px 3px black;
            height: 400px;
            width: 50px;
            /* 幅を広げて表示 */
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 20px;
            position: relative;
            writing-mode: vertical-rl;
            font-size: medium;
            -webkit-appearance: none;
            -webkit-border-radius: 0;
            font: inherit;
        }

        #back {
            background-color: white;
            border: 1px solid black;
            box-shadow: 1px 2px 3px black;
            height: 400px;
            width: 50px;
            /* 幅を広げて表示 */
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            position: relative;
            writing-mode: vertical-rl;
            font-size: medium;
            -webkit-appearance: none;
            -webkit-border-radius: 0;
            font: inherit;

        }

        #seeable_id {
            margin: 10px 0;
        }

        #discription {
            padding: 10px;
            border: 1px solid black;
            box-shadow: 1px 2px 3px black;
            width: 80%;
            max-width: 800px;
            margin: 20px 0;
        }

        #qrcode {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        #right {
            font-size: 30px;
            display: inline-block;
            transition: transform 0.3s ease;
            margin: 10px;
            margin-right: 20px;
        }

        #left {
            font-size: 15px;
            font-weight: bolder;
            margin: 10px;
            display: inline-block;
            transition: transform 0.3s ease;
        }

        @media screen and (max-width: 500px) {
            #back {
                background-color: white;
                border: 1px solid black;
                box-shadow: 1px 2px 3px black;
                height: 30%;
                width: 40px;
                /* 幅を広げて表示 */
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 20px;
                position: relative;
                writing-mode: vertical-rl;
                font-size: medium;
            }

            #go {
                background-color: white;
                border: 1px solid black;
                box-shadow: 1px 2px 3px black;
                height: 30%;
                width: 40px;
                /* 幅を広げて表示 */
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-left: 20px;
                position: relative;
                writing-mode: vertical-rl;
                font-size: medium;
            }

        }
    </style>
</head>

<body>
    <h1>ユーザーメイン</h1>
    <div class="message">
        <p><?php echo $enter ?> <br> <?php echo $waittime ?></p>
    </div>
    <div id="qrcode" class="container">
        <!-- #backボタンをQRコードの左側に配置 -->
        <div class="button">
            <form action="https://somefes.junzs.net/" id="backForm">
                <button type="submit" id="back">染谷祭詳細<span id="left">V</span></button>
            </form>
        </div>
        <div class="qrcode-container">
            <select id="imageSelect">
                <option value="show">QRコードを表示</option>
                <option value="hide">QRコードを非表示</option>
            </select>
            <canvas id="newImage" alt="QRコード"></canvas>
            <p id="seeable_id">あなたのIDは
                <?php echo $seeable_id ?> です <br> このIDを使っても入場などの処理ができます
            </p>
        </div>
        <div class="button">
            <form action="waitlist.php" id="goForm">
                <button type="submit" id="go">待ち時間一覧<span id="right">^</span></button>
            </form>
        </div>
    </div>
    <p id="discription">入場中です。というのは入場してるけど出場処理をしていないときに表示されます。<br> 入場したクラスで出場処理をするか、他クラスで入場処理をしてください。 <br>
        （なるべく出場処理をしてください。）</p>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>document.addEventListener('DOMContentLoaded', () => {
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

            // QRコードをPNG形式に変換
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

            // ボタンをクリックしたときの処理
            document.getElementById('go').addEventListener('click', (event) => {
                event.preventDefault(); // デフォルトのフォーム送信を防止
                const rightSpan = document.getElementById('right');
                rightSpan.style.transform = 'translateX(40px)'; // 右に40px移動

                // 2秒後にフォームを送信
                setTimeout(() => {
                    document.getElementById('goForm').submit();
                }, 700);
            });

            document.getElementById('back').addEventListener('click', (event) => {
                event.preventDefault(); // デフォルトのフォーム送信を防止
                const leftSpan = document.getElementById('left');
                leftSpan.style.transform = 'translateX(-40px)'; // 左に40px移動

                // 2秒後にフォームを送信
                setTimeout(() => {
                    document.getElementById('backForm').submit();
                }, 700);
            });
        });

    </script>
</body>

</html>