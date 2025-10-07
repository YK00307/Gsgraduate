<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__. '/pdo.php';

// PDOエラーを例外で投げるように
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $level = $_GET['level'] ?? 'pre1';
    $stage = $_GET['stage'] ?? 1;
    $chapter = $_GET['chapter'] ?? 1;

    $sql = "SELECT id, word_en, word_jp, word_def, example_en, example_jp
            FROM words_stages_complete
            WHERE exam_level = :level
                AND stage_id = :stage
                AND chapter_id = :chapter
            ORDER BY id
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':level' => $level,
        ':stage' => $stage,
        ':chapter' => $chapter
    ]);

    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($words as &$word) {
        $correct = $word['word_en'];

        $wrongChoices = array_column(
            array_filter($words, fn($w) => $w['id'] !== $word['id']),
            'word_en'
        );

        shuffle($wrongChoices);
        $wrongChoices = array_slice($wrongChoices, 0, 3);

        $choices = $wrongChoices;
        $choices[] = $correct;
        shuffle($choices);

        $word['choices'] = $choices;
    }

    echo json_encode([
        'ok' => true,
        'items' => $words
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // ここでJSON形式のエラーを返す
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
