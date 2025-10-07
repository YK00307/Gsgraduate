<?php
require 'pdo.php';

$id = intval($_GET['id']);

// 指定された単語を取得
$stmt = $pdo->prepare("SELECT * FROM words_stages_complete WHERE id=?");
$stmt->execute([$id]);
$word = $stmt->fetch(PDO::FETCH_ASSOC);

// 他の単語からランダムに3つ選んで選択肢にする
$stmt = $pdo->prepare("SELECT word_en FROM words_stages_complete WHERE id != ? ORDER BY RAND() LIMIT 3");
$stmt->execute([$id]);
$distractors = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 正解を追加してシャッフル
$choices = $distractors;
$choices[] = $word['word_en'];
shuffle($choices);

// 出力用データを整形
$result = [
    "ok" => true,
    "word" => $word,
    "choices" => $choices
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
