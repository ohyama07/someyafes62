<?php
require_once 'config.php';

if (isset($_COOKIE['userid']) && isset($_COOKIE['seeable_id'])) {
    $userid = $_COOKIE['userid'];
    $seeable_id = $_COOKIE['seeable_id'];
} else {
    header('Location: ../entrance/student/index.php');
    exit;
}

$waittime = 0;
$time = time();

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT * FROM queue WHERE enter IS NULL AND userid = :userid');
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {       //MEMO 予約しているクラスがなかったらまだ入場していないと表示 入場していたら入場中と表示
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
        $waittime = "--:--";
    } elseif (count($rows) === 1) { //MEMO 1箇所だけ予約していたら予約中のクラスを表示
        foreach ($rows as $row) {
            $class = $row['class'];
        }
        $enter = "予約中のクラス:" . $class;
        function calculateWaitTime($class, $userid)
        {
            global $dsn, $user, $password;
            try {
                $pdo = new PDO($dsn, $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // `capacity`を取得
                try {
                    $stmt = $pdo->prepare('SELECT capacity FROM class WHERE classname = :class');
                    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $capacity = $row['capacity'];
                } catch (PDOException $e) {
                    echo $e->getMessage();
                    exit;
                }


                //待ち時間を計算
                try {
                    $stmt = $pdo->prepare('SELECT row_no FROM (SELECT userid, ROW_NUMBER() OVER (ORDER BY start ASC) AS row_no FROM queue WHERE class = :class) AS ranked_queue WHERE userid = :userid');
                    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $waits = $row['row_no'];
                } catch (PDOException $e) {
                    echo $e->getMessage();
                    exit;
                }

                try {
                    $stmt = $pdo->prepare('SELECT entering.enter, TIMESTAMPDIFF(SECOND, entering.enter, NOW()) as duration, entering.row_no, waiting.userid FROM (SELECT enter, leaving, ROW_NUMBER() OVER (ORDER BY enter ASC) AS row_no FROM queue WHERE enter IS NOT NULL AND leaving IS NULL AND class = :class) AS entering INNER JOIN (SELECT userid, enter, leaving, ROW_NUMBER() OVER (ORDER BY enter ASC) AS row_no FROM queue WHERE enter IS NULL AND class = :class) AS waiting on entering.row_no = waiting.row_no WHERE userid = :userid');//予約した順番と同じ順番の人が入ってから何分経ったかを計算(予約したとき前から2番目なら、入場している中の前から2番目の人の時間をもらってくる)
                    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$row) {
                        $reservingnum = 0;
                    } else {
                        $reservingnum = $row['duration'];//現在時刻から何分経ったかってこと
                    }
            
            
            
                    $stmt = $pdo->prepare('SELECT AVG(TIMESTAMPDIFF(SECOND, enter,leaving)) AS avg_waitingtime FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NOT NULL AND leaving <> 0 AND leaving - enter <= 900'); //滞在時間が15分以内のものを取って平均を計算する。
                    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $avg_waitingtime = $row['avg_waitingtime'];
            
                } catch (PDOException $e) {
                    echo $e->getMessage();
                    exit;
                }



                if ($waits <= $capacity) {
                    $expect_waittime = $avg_waitingtime - $reservingnum;
                    $expect_waittime = round($expect_waittime);
                    if ($expect_waittime <= 0) {
                        ;
                    } else {
                        $expect_waittime = round($expect_waittime / 60);
                    }
                } else {
                    $expect_waittime = $avg_waitingtime * $waits / $capacity / 60;
                    $expect_waittime = round($expect_waittime);
                }

            } catch (PDOException $e) {
                echo 'データベースエラー: ' . $e->getMessage();
                return null;
            } catch (Exception $e) {
                echo 'エラー: ' . $e->getMessage();
                return null;
            }
        }
        $waittime = calculateWaitTime($class, $userid);

        try {
            $stmt = $pdo->prepare('SELECT permit FROM queue WHERE enter IS NULL AND permit IS NOT NULL AND class = :class AND userid = :userid');
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $enter = "入場できます。該当クラスに行って入場して下さい";
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }

    } elseif (count($rows) >= 2) {  //MEMO 二か所以上あったら(多分到達しないが、)最新のクラスのみを表示
        $stmt = $pdo->prepare('SELECT class, start FROM queue WHERE userid = :userid AND enter IS NULL LIMIT 1');
        $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            foreach ($rows as $row) {
                $stmt = $pdo->prepare('SELECT expecttime FROM class WHERE classname = :class');
                $stmt->bindValue(':class', $row['class'], PDO::PARAM_STR);
                $stmt->execute();
                $time = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($time) {
                    $enter .= "予約中のクラス: " . $row['class'] . " 予想入場時刻: " . $time['expecttime'] . "<br>";
                } else {
                    $enter .= "予約中のクラス:" . $row['class'] . "予想入場時刻: --:-- <br>";
                }
            }
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

        #discription {
            padding: 10px;
            border: 1px solid black;
        }
    </style>
</head>

<body>
    <h1>ユーザーメイン</h1>
    <p><?php echo $enter . "<br>" . "入場予想時刻:" . $waittime; ?></p>
    <div id="qrcode">
        <select id="imageSelect">
            <option value="show">QRコードを表示</option>
            <option value="hide">QRコードを非表示</option>
        </select>
        <div><canvas id="newImage" alt="QRコード"></div>
        <p id="seeable_id">あなたのIDは <?php echo $seeable_id ?> です <br> このIDを使っても入場などの処理ができます</p>
    </div>
    <a href="waitlist.php">待ち時間一覧表示ページへ</a>
    <p id="discription">入場中です。というのは入場してるけど出場処理をしていないときに表示されます。<br> 入場したクラスで出場処理をするか、他クラスで入場処理をしてください。 <br>
        （この表示のまま帰られたとしても処理に大きくは影響はしません。が、なるべく出場処理をしてください。）</p>

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
</body>

</html>