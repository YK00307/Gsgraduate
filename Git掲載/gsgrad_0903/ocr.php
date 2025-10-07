<?php
require 'vendor/autoload.php';
use OpenAI\Client;

$client = OpenAI::client('');

// 画像ファイルを取得
$tmpPath = $_FILES['file']['tmp_name'];
$base64 = base64_encode(file_get_contents($tmpPath));

$response = $client->chat()->create([
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'Extract the English text from this image.'],
        ['role' => 'user', 'content' => [
            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64," . $base64]]
        ]]
    ],
]);

$text = $response['choices'][0]['message']['content'];
echo json_encode(["text" => $text]);
