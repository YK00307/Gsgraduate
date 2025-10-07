<?php
require_once '/home/rivside/.php.config/config/apikey.php';
header("Content-Type: application/json");
$input = json_decode(file_get_contents("php://input"), true);
$endpoint = $input["endpoint"] ?? "";
$model    = "";
$body     = [];
switch($endpoint) {
    case "chat":
        $url = "https://api.openai.com/v1/chat/completions";
        $model = "gpt-4o-mini";
        $body = json_encode([
            "model" => $model,
            "messages" => $input["messages"] ?? []
        ]);
        break;
    case "image":
        $url = "https://api.openai.com/v1/images/generations";
        $model = "dall-e-3";
        $body = json_encode([
            "model" => $model,
            "prompt" => $input["prompt"] ?? "",
            "size" => $input["size"] ?? "512x512"
        ]);
        break;
    case "audio_speech":
        $url = "https://api.openai.com/v1/audio/speech";
        $model = "gpt-4o-mini-tts";
        $body = json_encode([
            "model" => $model,
            "text" => $input["text"] ?? "",
            "voice" => $input["voice"] ?? "alloy"
        ]);
        break;
    case "audio_transcription":
        $url = "https://api.openai.com/v1/audio/transcriptions";
        $model = "gpt-4o-mini-transcribe";
        $body = json_encode([
            "model" => $model,
            "file"  => $input["file"] ?? "" // base64やMultipartで送信
        ]);
        break;
    default:
        echo json_encode(["error" => "Unknown endpoint"]);
        exit;
}

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $response = curl_exec($ch);
    curl_close($ch);

    // JSONからbase64を取り出す
    $json = json_decode($response, true);
    if (isset($json['audio'])) {
        $audioData = base64_decode($json['audio']);
        header("Content-Type: audio/mpeg");
        echo $audioData;
    } else {
        header("Content-Type: application/json");
        echo $response; // エラーなど
    }
    exit;