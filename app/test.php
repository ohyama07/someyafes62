<?php
$today = (int)(new DateTimeImmutable())->format('YmdHisu');
$today_hex = dechex($today); // Unixタイムスタンプを16進数文字列に変換
$salt = rand(1, 65535); // ランダムなソルトを生成

// ハッシュ値を計算するためのデータを準備
$data = $today_hex . $salt;

// SHA-256でハッシュ値を計算
$hash = hash('sha256', $data);

// CRC32でチェックサムを計算
$crc32 = crc32($data);

// ユーザーIDを生成
$userid = '0' . sprintf("%08x", $crc32) . $hash; // CRC32を16進数文字列に変換して連結

var_dump($userid);