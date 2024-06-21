<?php
function inLimited($pdo, $class){
    $remaining = inRemaining($class);
    //前から許容人数番目までを計算する関数
        $stmt = $pdo->prepare("SELECT enter FROM queue WHERE class = :class AND start IS NOT NULL AND leaving IS NULL ORDER BY start ASC LIMIT :remaining");
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->bindValue(':remaining', $remaining, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}