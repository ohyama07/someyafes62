<?php
include 'config.php';
function waittimeCal($class, $userid) {
global $dsn, $user, $password;//引数が普通、glovalは例外。今はconfigが共通だからglobalを使った
try {
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
    $stmt = $pdo->prepare('SELECT entering.enter, now() - entering.enter as duration, entering.row_no, waiting.userid FROM (SELECT enter, leaving, ROW_NUMBER() OVER (ORDER BY enter ASC) AS row_no FROM queue WHERE enter IS NOT NULL AND leaving IS NULL AND class = :class) AS entering INNER JOIN (SELECT userid, enter, leaving, ROW_NUMBER() OVER (ORDER BY enter ASC) AS row_no FROM queue WHERE enter IS NULL AND class = :class) AS waiting on entering.row_no = waiting.row_no WHERE userid = :userid');//FIXME ここの計算をimestanpdiffを使ったものにする
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


    
$stmt= $pdo->prepare('SELECT AVG(TIMESTAMPDIFF(SECOND, enter,leaving)) AS avg_waitingtime FROM queue WHERE class = :class AND enter IS NOT NULL AND leaving IS NOT NULL AND leaving <> 0 AND leaving - enter <= 900');
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
            echo "あと{$expect_waittime}分後に入場できる見込みです";
        }
    } else {
        echo "ここからが本題";
var_dump($avg_waitingtime, $waits, $capacity);
        $expect_waittime = $avg_waitingtime * $waits / $capacity / 60;
        $expect_waittime = round($expect_waittime);
        echo "あと{$expect_waittime}分後に入場できる見込みです1";
    }
}