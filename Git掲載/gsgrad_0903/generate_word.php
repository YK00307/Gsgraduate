<?php
error_reporting(0);
ini_set('display_startup_errors', 1);

session_start();
require_once "api/pdo.php";

header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// apikey.php からキー取得
$apiKeys = require_once "/home/rivside/.php.config/config/apikey.php";
$OPENAI_API_KEY = $apiKeys["OPENAI_API_KEY"];

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "ログインが必要です"]);
    exit;
}
$user_id = $_SESSION['user_id'];

// 入力取得
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!is_array($input) || empty($input["word"])) {
    echo json_encode(["success" => false, "message" => "入力が不正です", "raw" => $raw]);
    exit;
}
$word = trim($input["word"]);

// OpenAIに送るプロンプト（※DBに合わせて定義も削減）
$prompt = $prompt = "あなたは英単語辞典アシスタントです。
次に与える英単語について、指定のJSON形式でのみ出力してください。
日本語での意味と、10〜20語程度の英語例文、およびその日本語訳を出力してください。
※説明や挨拶などの余分な文は一切含めないでください。
出力フォーマット:
{
  \"meaning\": \"日本語での意味（簡潔に）\",
  \"sentence\": \"英語の例文（10〜20語程度）\",
  \"sentence_ja\": \"その例文の日本語訳\"
}

単語: {$word}";


$ch = curl_init("https://rivside.sakura.ne.jp/gsgrad_0903/openai.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "endpoint" => "chat",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ], JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
if ($response === false) {
    echo json_encode(["success" => false, "message" => "cURLエラー: " . curl_error($ch)]);
    exit;
}
curl_close($ch);

// レスポンス解析
$result = json_decode($response, true);
$content = $result["choices"][0]["message"]["content"] ?? null;

if (!$content) {
    echo json_encode(["success" => false, "message" => "AI応答が不正です"]);
    exit;
}

$data = json_decode($content, true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "AI応答のJSON解析失敗", "raw" => $content]);
    exit;
}

// DB保存
try {
    $stmt = $pdo->prepare("
        INSERT INTO custom_words (user_id, word, meaning, sentence)
        VALUES (:user_id, :word, :meaning, :sentence)
        ON DUPLICATE KEY UPDATE
            meaning = VALUES(meaning),
            sentence = VALUES(sentence)
    ");
    $stmt->execute([
        ":user_id" => $user_id,
        ":word" => $word,
        ":meaning" => $data["meaning"] ?? "",
        ":sentence" => $data["sentence"] ?? ""
    ]);

    echo json_encode(["success" => true, "word" => $word, "data" => $data]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
