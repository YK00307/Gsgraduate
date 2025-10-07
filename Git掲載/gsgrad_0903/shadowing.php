<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $endpoint = $_POST['endpoint'] ?? '';

    if ($endpoint === 'transcription' && isset($_FILES['audio'])) {
        // 文字起こし処理
        $audioPath = $_FILES['audio']['tmp_name'];
        $ch = curl_init("https://api.openai.com/v1/audio/transcriptions");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "file" => new CURLFile($audioPath),
            "model" => "gpt-4o-mini-transcribe"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        $text = $data['text'] ?? '';

        echo json_encode(['text' => $text]);
        exit;

    } elseif ($endpoint === 'chat') {
        // chat for evaluation
        $messages = $_POST['messages'] ?? [];
        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gpt-4o-mini",
            "messages" => $messages
        ]));
        $response = curl_exec($ch);
        curl_close($ch);
        echo $response;
        exit;
    }

    // それ以外はエラー処理
}
?>