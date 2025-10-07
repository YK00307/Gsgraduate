<?php
session_start();
require_once "api/pdo.php"; // PDO接続 (例: $pdo)

// ログインしているユーザーID
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "ログインが必要です"]);
    exit;
}
$user_id = $_SESSION['user_id'];

// JSONデータを受け取る
$data = json_decode(file_get_contents("php://input"), true);
$word = $data["word"] ?? "";
$meaning = $data["meaning"] ?? "";
$sentence = $data["sentence"] ?? "";
$shortMeaning = $data["shortMeaning"] ?? "";

// バリデーション
if ($word === "") {
    echo json_encode(["success" => false, "message" => "単語が空です"]);
    exit;
}

// DBに保存
try {
    $stmt = $pdo->prepare("INSERT INTO custom_words (user_id, word, meaning, sentence, short_meaning)
                           VALUES (:user_id, :word, :meaning, :sentence, :short_meaning)
                           ON DUPLICATE KEY UPDATE meaning = VALUES(meaning), sentence = VALUES(sentence)");
    $stmt->execute([
        ":user_id" => $user_id,
        ":word" => $word,
        ":meaning" => $meaning,
        ":sentence" => $sentence,
        ":short_meaning" => $shortMeaning
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
