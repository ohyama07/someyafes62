<?php
include 'qrcode.php';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>making_qrcode</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script>
</head>

<body>
    <h1>QRコード生成</h1>
    <div class="qrblock"></div>
    <div id="from-cookie"></div>
    <div id="qrOutput"></div>
    <div>
        <p id="qrString"></p>
    </div>
    <canvas id="qr"></canvas>
    <p id="qrText"></p>
    <div><img id="newImg"></div>
    </div>
    <script>
        let qrcode = "<?php makingQrcode() ?>";
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
            document.getElementById('qrText').textContent = `あなたのIDは 0<?php echo $seeable_id['id'] ?> です`;
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
            /* ローカルストレージに保存
            localStorage.setItem('query', query);*/

        });
    </script>
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

        @media (max-width: 500px) {}
        /*FIXME ここ中途半端 */
    </style>
</body>

</html>