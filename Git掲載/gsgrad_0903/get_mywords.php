<?php
session_start();
require_once "api/pdo.php";

// ログイン確認
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT word, meaning, sentence, short_meaning FROM custom_words WHERE user_id = :user_id ORDER BY id DESC");
    $stmt->execute([':user_id' => $user_id]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($words);
} catch (Exception $e) {
    echo json_encode([]);
}
