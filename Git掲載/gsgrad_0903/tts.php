<?php
header("Content-Type: audio/mpeg");

$input = json_decode(file_get_contents("php://input"), true);
$text = $input["text"] ?? "";

$ch = curl_init("https://api.openai.com/v1/audio/speech");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer ",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-4o-mini-tts",
    "voice" => "alloy",
    "input" => $text
]));

$response = curl_exec($ch);
curl_close($ch);

echo $response;
