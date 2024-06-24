<?php
include 'idGenerate.php';
list($seeable_id,$userid) = toEntranceId();

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID生成</title>
    <style>
        * {
            text-align: center;
        }
    </style>
</head>

<body>
    <p id="result"></p>
    <p id="timestamp"></p>
    <div class="generate">
        <button type="button" id="generator" style="width: 4em; height: 4em;">id生成</button>
    </div>
    <p>ID生成ボタンまたはリロードで生成できます。</p>
    <script>
        let now = new Date();

        // HH:mm:ss 形式の時刻を取得するための関数を定義
        function getFormattedTime(date) {
            let hours = date.getHours().toString().padStart(2, '0'); // 時間を取得し、2桁にパディング
            let minutes = date.getMinutes().toString().padStart(2, '0'); // 分を取得し、2桁にパディング
            let seconds = date.getSeconds().toString().padStart(2, '0'); // 秒を取得し、2桁にパディング
            return `${hours}:${minutes}:${seconds}`; // HH:mm:ss 形式で返す
        }

        // 現在の時刻を HH:mm:ss 形式で取得
        let formattedTime = getFormattedTime(now);

        let result = document.querySelector("#result");
        let time = document.querySelector("#timestamp");
        let generator = document.querySelector("#generator");
        let clickcount = 0;
        <?php sleep(1); ?>
        result.textContent = "0" + <?php echo $seeable_id ?>;
        time.textContent =  formattedTime + "に生成済";
        generator.addEventListener('click', () => {
            clickcount++
            if (clickcount === 1) {
                location.reload();
                clickcount = 0;
            }
        })
    </script>
</body>

</html>