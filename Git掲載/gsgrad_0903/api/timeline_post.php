<?php
session_start();
require_once "pdo.php";

header("Content-Type: application/json; charset=UTF-8");

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status"=>"error","message"=>"ログインが必要です"]);
    exit;
}

$json = file_get_contents("php://input");
$data = json_decode($json, true);

$content = trim($data['content'] ?? '');

if ($content === "") {
    echo json_encode(["status"=>"error","message"=>"投稿内容が空です"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO timeline_posts (user_id, content) VALUES (?, ?)");
$stmt->execute([$userId, $content]);

echo json_encode(["status"=>"ok"]);
?>

