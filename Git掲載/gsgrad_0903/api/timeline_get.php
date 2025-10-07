
<?php
session_start();
require_once "pdo.php";

header("Content-Type: application/json; charset=UTF-8");

$stmt = $pdo->query("
    SELECT tp.id, tp.content, tp.created_at, u.username
    FROM timeline_posts tp
    JOIN users u ON tp.user_id = u.id
    ORDER BY tp.created_at DESC
    LIMIT 50
");

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status"=>"ok","posts"=>$posts], JSON_UNESCAPED_UNICODE);
?>