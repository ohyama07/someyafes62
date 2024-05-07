<?php
require_once 'config.php';
$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['userid'];
$class = $_SESSION['username'];

//修正:定員を入れるcapasityテーブルを追加

// ①そのuserに関するレコードがない(初入場)
$stmt = $pdo->prepare("SELECT * FROM enter_records WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

    //許容範囲計算 ただし定員を表すテーブルcapacityを追加済という前提
    // 関数で定義しているから$remaining = getRemainingCapacity($pdo, $class);とやればいつでも呼び出せる
    function getRemainingCapacity($pdo, $class) {
        // capacityからそのクラスの定員を持ってくる
        $stmt = $pdo->prepare("SELECT capacity FROM capacity WHERE class = :class");
        $stmt->execute(['class' => $class]);
        $capacity = $stmt->fetch(PDO::FETCH_ASSOC)['capacity'];
        if ($capacity === false) {
            echo "定員を取得できませんでした";
            return;
        }
    
        // start IS NOT NULL && leaving IS NULL　をカウントする
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enter_records WHERE start IS NOT NULL AND leaving IS NULL");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($count === false) {
            echo "値がありません";
            return;
        }
    
        // 定員の値とカウントした値を引き算する
        return $capacity - $count;
    }
if (!$row) {
    $stmt = $pdo->prepare("INSERT INTO enter_records (user_id) VALUES (:user_id)");
    $stmt->execute(['user_id' => $user_id]);
    // 出し物の定員と現在の入場者数を参照する処理 ➡許容範囲計算ってことよね
    $remaining = getRemainingCapacity($pdo, $class);
    // 出し物の定員未満ならenterにもstartと同じ値を入れる処理を追加する
    if ($remaining !== 0) {
        /*
        startの値を持ってくる
        前から$remaining番目にあるかどうか
        */
    }

    echo "入場してください";
    exit;
}

// ④userとstartとenterとleaving(全て)にレコードがある(再入場)
$stmt = $pdo->prepare("SELECT * FROM enter_records WHERE user_id = :user_id AND leaving_id IS NOT NULL");
$stmt->execute(['user_id' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    // 再入場の処理を行う
    // leaving_recordsテーブルにデータを挿入する処理を追加する　→修正:　疑問だが、lezving_recordsとleavingは別物？
    // 再入場時のメッセージを出力する　→修正：　入場しましたでいいと思う
    echo "再入場しました";
    exit;
}

// ②userとstartにレコードがある
$stmt = $pdo->prepare("SELECT * FROM enter_records WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    // 入場可能かチェックする処理を行う
    // 入場処理を行う条件に合致するかを確認する処理を追加する
    // 入場処理が許可された場合のメッセージを出力する
    echo "入場してください";
    // 入場処理が許可されなかった場合のメッセージを出力する
    echo "入場できません　再度時間を空けて入場してください";
    exit;
}

// 3のとき
// leavingに現在のタイムスタンプを入れ、①へ ➡修正:　leavingに0など適当な数字を入れる
$stmt = $pdo->prepare("INSERT INTO leaving_records (user_id) VALUES (:user_id)");
$stmt->execute(['user_id' => $user_id]);
echo "入場できません";





?>