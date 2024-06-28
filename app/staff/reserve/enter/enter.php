<?php
if (!isset($_COOKIE['class'])) {
    header('Location: ../../login/login.php');
    exit;
}

if ($_COOKIE['class'] === "2年4組") {
    header('Location: ../../twofour/enter.html');
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <title>入場</title>
    <style>
        .hidden {
            display: none;
        }

        #wrapper {
            position: relative;
        }
        #video {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #camera-canvas,
        #rect-canvas {
            position: absolute;
            top: 0;
            left: 0;
        }
        #resultForm {
            display: flex;
            justify-content: center;
            position: relative; /* relativeに変更 */
            top: 50px; /* canvas要素の高さに合わせて調整 */
            margin-top: 10px; /* 適切なスペースを追加 */
        }
        #serch {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        .button {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            padding: 10px;
            background-color: #f5f5f5;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
        }
        @media screen and (max-width: 500px) {
            #resultForm {
                display: flex;
            }
        }
    </style>
</head>

<body>
    <p id="message"></p>
    <div id="wrapper">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="camera-canvas"></canvas>
        <canvas id="rect-canvas"></canvas>

        <form action="index.php" method="POST" id="resultForm">
            ID: <output id="search"></output>
            <input type="text" name="userid" id="userid">
        </form>
    </div>

    <script src="./jsQR.js"></script>
    <video id="video" width="640" height="480" autoplay></video>

    <div class="button">
        <!--<button type="button" id="go" name="go">定員追加ページへ</button>-->
        <button type="button" id="back" name ="back">スタッフメインページへ</button>
        <button type="button" id="capa" name="capa">定員確認ページへ</button>
    </div>
    <script>
        // Webカメラの起動
        const video = document.getElementById('video');
        let contentWidth;
        let contentHeight;


        // カメラ映像のキャンバス表示
        const cvs = document.getElementById('camera-canvas');
        const ctx = cvs.getContext('2d');
        const canvasUpdate = () => {
            cvs.width = contentWidth;
            cvs.height = contentHeight;
            ctx.drawImage(video, 0, 0, contentWidth, contentHeight);
            requestAnimationFrame(canvasUpdate);
        }
        const media = navigator.mediaDevices.getUserMedia({ audio: false, video: { width: 640, height: 480 } })
            .then((stream) => {
                video.srcObject = stream;
                video.onloadeddata = () => {
                    video.play();
                    contentWidth = Math.floor(video.clientWidth);
                    contentHeight = Math.floor(video.clientHeight);
                    canvasUpdate(); // 次で記述
                    checkImage(); // 次で記述
                }
            }).catch((e) => {
                console.log(e);
            });



        // QRコードの検出
        const rectCvs = document.getElementById('rect-canvas');
        const rectCtx = rectCvs.getContext('2d');
        let qrFlag = false;

        let resultForm = document.getElementById('resultForm');
        resultForm.addEventListener('submit', function (event) {
            let userid = document.getElementById('userid');
            userid.classList.add('hidden');//ADD 16日　送信する中身を見えなくした
            if (!qrFlag) {
                while (userid.value.length < 73) {
                    userid.value += "0";
                }

                //userid = userid.value.padEnd(73, '0');
                qrFlag = true;
            } else if (userid.value.length === 73) {
                qrFlag = true;
                resultForm.submit();
            }
        });




        const checkImage = () => {
            const imageData = ctx.getImageData(0, 0, contentWidth, contentHeight);//エラーは問題ない
            console.log(typeof imageData);
            const code = jsQR(imageData.data, contentWidth, contentHeight);

            if (code) {
                document.getElementById('search').value = "見つかりました";
                drawRect(code.location);
                userid.value = code.data;
                qrFlag = true;
                resultForm.submit();
            } else {
                document.getElementById('search').value = "見つかりません";
                rectCtx.clearRect(0, 0, contentWidth, contentHeight);
            }

        }


        setInterval(checkImage, 250);

        /*let go = document.querySelector("#go");
        go.addEventListener('click', () => {
            window.location.href = 'addCapacity.html';
        })*/
        let back = document.querySelector("#back");
        back.addEventListener('click', () => {
            window.location.href = '../../login/index.php';
        })

        let capa = document.querySelector("#capa");
        capa.addEventListener('click', () => {
            window.location.href = '../../login/addCapacity.php';
        })


        // 四辺形の描画
        const drawRect = (location) => {
            rectCvs.width = contentWidth;
            rectCvs.height = contentHeight;
            drawLine(location.topLeftCorner, location.topRightCorner);
            drawLine(location.topRightCorner, location.bottomRightCorner);
            drawLine(location.bottomRightCorner, location.bottomLeftCorner);
            drawLine(location.bottomLeftCorner, location.topLeftCorner)
        }

        // 線の描画
        const drawLine = (begin, end) => {
            rectCtx.lineWidth = 4;
            rectCtx.strokeStyle = "#F00";
            rectCtx.beginPath();
            rectCtx.moveTo(begin.x, begin.y);
            rectCtx.lineTo(end.x, end.y);
            rectCtx.stroke();
        }

        let message = document.querySelector("#message");
        message.textContent = "<?php echo $_COOKIE['class'] . 'としてログイン中'; ?>"

    </script>

</body>

</html>