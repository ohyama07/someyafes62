<?php
require_once 'config.php';

$today = strtotime("now");
$today_hex = dechex($today); // Unixタイムスタンプを16進数文字列に変換
$salt = rand(1, 10); // ランダムなソルトを生成

// ハッシュ値を計算するためのデータを準備
$data = $today_hex . $salt;

// SHA-256でハッシュ値を計算
$hash = hash('sha256', $data);

// CRC32でチェックサムを計算
$crc32 = crc32($data);

// ユーザーIDを生成
$userid = '0' . sprintf("%08x", $crc32) . $today_hex; // CRC32を16進数文字列に変換して連結





try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("INSERT INTO usersid (userid) VALUES (:userid)");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usersid WHERE userid = :userid");
    $stmt->bindValue(':userid', $userid);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $seeable_id = $row['id'];
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

session_start();
$_SESSION['userid'] = $userid;
if (!isset($_SESSION['load_flag'])) {

    // QRコード生成関数
    function makingQrcode($id)
    {
        return $id ? "ID: $id" : "IDが見つかりません";
    }

    // 初回QRコード生成
    $qrcode = makingQrcode($seeable_id);
    $_SESSION['load_frag'] = true;
}
session_write_close();

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>making_qrcode</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script>
    <style>
        /*#qrOutput {
        display: none;
    }*/
        * {
            text-align: center;
        }

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



        /*FIXME ここ中途半端 media属性がここにあった*/
    </style>
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
    <p id="genTime"></p>
    <div class="generate">
        <button type="button" id="generator" style="width: 4em; height: 4em;">id生成</button>
    </div>
    <p id="result"></p>
    <p>注意
        たくさんリロードするとリロードした回数だけサーバーにアクセスする仕様になっちゃってますので<br>
        あんまりリロードしないでください
    </p>
    <script>
        let genTime = document.querySelector("#genTime");
        let result = document.querySelector("#result");
        // PHPから取得したseeable_idをJavaScriptに渡す
        let seeableId = "<?php echo addslashes($seeable_id); ?>";
        let qrCodeGenerated = false;

        // DOMが完全に読み込まれた後に処理を実行
        document.addEventListener('DOMContentLoaded', () => {
            let generator = document.getElementById('generator');
            generator.addEventListener('click', () => {
                if (!qrCodeGenerated) {
                    
                    // 1回目のクリックでQRコードを生成
                    generateQRCode(seeableId);
                    qrCodeGenerated = true;
                    result.textContent = "ボタンタップで次へ"
                } else {
                    // 2回目以降のクリックでページをリロード
                    location.reload();
                    <?php
                    session_start();
                    unset($_SESSION['userid']);
                    session_write_close()
                        ?>
                }
            });//FIXME リロードしたら見えないところで生成しちゃう

            //ID出力用コード
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

        function generateQRCode(id) {
            // QRコードを生成
            let qr = new QRious({
                element: document.getElementById('qr'),
                value: id,
                size: 240, // QRコードのサイズを設定
                background: '#FFF',
                foreground: '#000'
            });
            // QRコードのIDを表示
            document.getElementById('qrText').textContent = `あなたのIDは 0${id} です`;
        }
    </script>
</body>

</html>