<?php

try {
    // ★★★ generate_word.info のコード全体をここに入れる ★★★
ini_set('display_errors', 1);
error_reporting(E_ALL);

// generate_word_info.php
error_reporting(0);
ini_set('display_startup_errors', 1);

session_start();
require_once "api/pdo.php"; // ← DB接続

// 一時的にこの下をすべてコメントアウトする
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// 🔑 APIキーを外部ファイルから取得
$apiKeys = require_once "/home/rivside/.php.config/config/apikey.php";
$OPENAI_API_KEY = $apiKeys["OPENAI_API_KEY"];

// 👤 ログインチェック
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "ログインが必要です"]);
    exit;
}
$user_id = $_SESSION['user_id'];

// 📥 入力データ取得
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!is_array($input) || empty($input["word"])) {
    echo json_encode(["success" => false, "message" => "入力が不正です", "raw" => $raw]);
    exit;
}
$word = trim($input["word"]);

// 🧠 OpenAIに送るプロンプト
$prompt = "あなたは英単語辞典アシスタントです。
次に与える英単語について、指定のJSON形式でのみ出力してください。
出力には以下の4項目をすべて含めてください：
1. 日本語での意味（簡潔に）
2. 英英辞典風の簡潔な英語定義（CEFR B2程度）
3. 10〜20語程度の英語例文
4. その例文の日本語訳
出力形式は必ず次のJSON形式にしてください。説明や挨拶などは絶対に不要です。

{
  \"meaning\": \"日本語での意味（簡潔に）\",
  \"definition\": \"英英辞典風の定義（英語）\",
  \"sentence\": \"英語の例文（10〜20語程度）\",
  \"sentence_ja\": \"その例文の日本語訳\"
}

単語: {$word}";

// 🌐 OpenAI呼び出し (自作 openai.php 経由)
$ch = curl_init("http://rivside.sakura.ne.jp/gsgrad_0903/openai.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "endpoint" => "chat",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
        "response_format": { "type": "json_object" }
    ], JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo json_encode(["success" => false, "message" => "AIからの応答がありません"]);
    exit;
}

// 🧩 openai.php からのレスポンス解析
$result = json_decode($response, true);
$ai_content = $result["choices"][0]["message"]["content"] ?? null;

if (!$ai_content) {
    echo json_encode(["success" => false, "message" => "AI出力が不正です", "raw" => $response]);
    exit;
}

// JSONパース（AIが正しい形式で返した想定）
$data = json_decode($ai_content, true);

error_log(print_r($data, true));

if (!is_array($data)) {
    echo json_encode(["success" => false, "message" => "AIのJSON解析に失敗しました", "raw" => $ai_content]);
    exit;
}

// DB登録：wordsテーブル更新
try {
    $stmt = $pdo->prepare("
    INSERT INTO words_stages_complete (word_en, word_jp, word_def, example_en, example_jp)
    VALUES (:word, :jp, :def, :en, :ja)
    ON DUPLICATE KEY UPDATE
        word_jp   = VALUES(word_jp),
        word_def  = VALUES(word_def),
        example_en = VALUES(example_en),
        example_jp = VALUES(example_jp)
");
$stmt->execute([
    ":word" => $word,
    ":jp"   => $data["meaning"] ?? null,
    ":def"  => $data["definition"] ?? null,
    ":en"   => $data["sentence"] ?? null,
    ":ja"   => $data["sentence_ja"] ?? null
]);

    echo json_encode([
        "success" => true,
        "message" => "{$word} の情報を登録しました。",
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "DB更新に失敗しました", "error" => $e->getMessage()]);
}

 // DB更新が成功した場合のJSON応答
    echo json_encode(["success" => true, "message" => "処理成功"]);

} catch (Exception $e) {
    // ★★★ 予期せぬエラーをここで捕捉し、JSONで返す ★★★
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "サーバーで予期せぬエラーが発生しました。",
        "error_detail" => $e->getMessage(),
        "error_file" => $e->getFile() . " on line " . $e->getLine()
    ]);
}
