<?php
include 'config.php';
function waittimeCal($class, $userid) {
global $dsn, $user, $password;//引数が普通、glovalは例外。今はconfigが共通だからglobalを使った
try {//定員を取る
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT capacity FROM class WHERE classname = :class');
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $capacity = $row['capacity'];
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
    if(!$row){
        $reservingnum = 0;
    } else {
        $reservingnum = $row['duration'];//現在時刻から何分経ったかってこと
    }


    
$stmt= $pdo->prepare('SELECT AVG(TIMESTAMPDIFF(SECOND, enter,leaving)) AS avg_waitingtime FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NOT NULL AND leaving <> 0 AND leaving - enter <= 900'); //滞在時間が15分以内のものを取って平均を計算する。
$stmt->bindValue(':class', $class, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_waitingtime = $row['avg_waitingtime'];
         
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM queue WHERE enter IS NULL AND class = :class');
    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
    $stmt->execute();
    $waits = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

    if($waits <= $capacity) {
        $expect_waittime =  $avg_waitingtime - $reservingnum;
        $expect_waittime = round($expect_waittime);
        if ($expect_waittime <= 0) {
            echo "まもなく入場できます";
        } else {
            $expect_waittime = round($expect_waittime / 60);
            echo "あと{$expect_waittime}分後に入場できる見込みです";
        }
    } else {
        $expect_waittime = $avg_waitingtime * $waits / $capacity / 60;
        $expect_waittime = round($expect_waittime);
        echo "あと{$expect_waittime}分後に入場できる見込みです";
    }//FIXME $waitsの取る範囲を考えないといけない。今のままじゃ誰でも同じ値が返ってくる

    try {
        $stmt = $pdo->prepare('SELECT expecttime FROM class WHERE classname = :class');
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($expect_waittime > $row['expecttime']) {
            $stmt = $pdo->prepare('UPDATE class SET expecttime = :expecttime WHERE classname = :class');
            $stmt->bindValue(':expecttime', $expect_waittime, PDO::PARAM_INT);
            $stmt->bindValue(':class', $class, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }
}