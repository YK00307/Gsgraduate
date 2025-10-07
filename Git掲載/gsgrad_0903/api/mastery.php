<?php
session_start();
require 'pdo.php';

// --- ログイン確認 --
$userId = $data['user_id'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);
$wordId = $data["wordId"] ?? null;
$result = $data["result"] ?? null;
$mode   = $data["mode"] ?? null;

if (!$userId || !$wordId || !$result) {
    echo json_encode(["ok" => false, "error" => "invalid input"]);
    exit;
}


// --- スコア計算 ---
$points = 0;

// ポイント計算
if ($result === "correct") {
    if ($mode === "mcq") {
        $points = 1;
    } elseif ($mode === "fill") {
        $points = 2;
    }
} elseif ($result === "incorrect") {
    $points = -0.5;
} elseif ($result === "mastered") {
    // 2回以上正解でmasteredをつける仕様なので、ここはボーナス加点
    $points = 5;
}

    // --- user_words を更新（なければ作成） ---
    $stmt = $pdo->prepare("
        INSERT INTO user_words (user_id, word_id, mastered, mistake_count, last_practiced)
        VALUES (:user_id, :word_id, 0, 0, NOW())
        ON DUPLICATE KEY UPDATE
            last_practiced = NOW(),
            mistake_count = CASE
                WHEN :result = 'incorrect' THEN mistake_count + 1
                ELSE mistake_count
            END,
            mastered = CASE
                WHEN :result = 'mastered' THEN 1
                ELSE mastered
            END
    ");
    $stmt->execute([
        ":user_id" => $userId,
        ":word_id" => $wordId,
        ":result"  => $result,
    ]);

    // --- usersテーブルのポイント更新 ---
$stmt = $pdo->prepare("UPDATE users SET points = points + :points WHERE id = :id");
$stmt->execute([
    ":points" => $points,
    ":id"     => $userId
]);

// --- 最新の累計ポイントを取得 ---
$stmt = $pdo->prepare("SELECT points FROM users WHERE id = :id");
$stmt->execute([":id" => $userId]);
$totalPoints = (int)$stmt->fetchColumn();

// --- JSONで返す ---
echo json_encode([
    "ok" => true,
    "points" => $points,
    "totalPoints" => $totalPoints
]);

// mastery.php の最後あたりに追加
$stmt = $pdo->prepare("UPDATE users SET points = points + :p WHERE id = :uid");
$stmt->execute([
    ":p" => $points,
    ":uid" => $userId
]);
