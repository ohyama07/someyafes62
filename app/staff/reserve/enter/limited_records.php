<?php
function inLimited($class){
    $remaining = inRemaining($class);
    global $dsn, $user, $password;
    //前から許容人数番目までを計算する関数
        try{
            $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT enter FROM queue WHERE class = :class AND start IS NOT NULL AND leaving IS NULL ORDER BY start ASC LIMIT :remaining");
        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
        $stmt->bindValue(':remaining', $remaining, PDO::PARAM_INT);
        $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}