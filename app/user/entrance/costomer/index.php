<?php
include '../../../staff/entrance/idGenerate.php';
require_once 'config.php';

if (!isset($_COOKIE['userid']) || !isset($_COOKIE['seeable_id'])) {
    list($seeable_id, $userid) = toEntranceId();
    setcookie('userid', $userid, time() + 86400, "/");
    setcookie('seeable_id', $seeable_id, time() + 86400, "/");

    header('Location:' . $_SERVER['PHP_SELF']);
    exit;
}

$userid = $_COOKIE['userid'];
$seeable_id = $_COOKIE['seeable_id'];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>来場者用</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script>
    <style>
        .wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #go {
            background-color: white;
            color: black;
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
        }

        #right {
            color: black;
            font-size: 30px;
            display: inline-block;
            transition: transform 0.3s ease;
            margin: 10px;
            margin-right: 20px;
        }

        #qrText {
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>QRコード生成</h1>
    <div class="wrap">
        <div class="qrblock"></div>
        <div id="from-cookie"></div>
        <div id="qrOutput"></div>
        <div>
            <p id="qrString"></p>
        </div>
        <canvas id="qr"></canvas>
        <div><img id="newImg"></div>
        <form action="../../main/index.php" method="POST" id="goForm">
            <button type="submit" id="go">メインページへ <span id="right">^</span></button>
        </form>
    </div>
    <p id="qrText"></p>
    <script>
        let cookie = document.cookie;
        let query = 0;
        let userid = "<?php echo $userid ?>";
        document.addEventListener('DOMContentLoaded', () => {
            query = userid.split(' ').join('+');
            let qr = new QRious({
                element: document.getElementById('qr'),
                value: query
            });

            //ID出力用コード
            document.getElementById('qrText').textContent = `あなたのIDは <?php echo $seeable_id ?> です`;
            qr.background = '#FFF';
            qr.backgroundAlpha = 0.8;
            qr.foreground = '#000000';
            qr.foregroundAlpha = 1.0;
            qr.level = 'L';
            qr.size = 240;
            // png出力用コード
            let cvs = userid;
            let png = cvs.toDataURL();
            document.getElementById("newImg").src = png;

            // PNGをサーバーに送信
            fetch('save_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ png: png })
            })
                .then(response => response.text())
                .then(data => {
                    console.log('Success:', data);
                })
                .catch((error) => {
                    console.error('Error:', error);
                });

            document.getElementById('go').addEventListener('click', (event) => {
                event.preventDefault(); // デフォルトのフォーム送信を防止
                const rightSpan = document.getElementById('right');
                rightSpan.style.transform = 'translateX(40px)'; // 右に40px移動

                // 2秒後にフォームを送信
                setTimeout(() => {
                    document.getElementById('goForm').submit();
                }, 700);
            });
        });

    </script>
    <?php
    //echo "3秒後にリダイレクトします";
    /*echo '<script>
    setTimeout(function(){
        window.location.href = "../../main/index.php";
    }, 1500);
    </script>';*/
    ?>
    <style>
        /*#qrOutput {
        display: none;
    }*/
        p.qrblock {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            /* viewportの高さに合わせる */
            text-align: center;
        }

        p.qrcomment {
            background: #eff1f5;
            text-align: center;
            color: #707070;
            padding: 7px 35px 8px;
            border-radius: 9999px;
            display: inline-block;
            margin: 0 0 25px;
            position: relative;
        }

        p.qrcomment:after {
            content: "";
            position: absolute;
            bottom: -25px;
            left: 50%;
            margin-left: -15px;
            border: 15px solid transparent;
            border-top: 15px solid #eff1f5;
            z-index: 1;
        }

        #qrOutput {
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-around;
            padding: 20px;
        }
    </style>
</body>

</html>