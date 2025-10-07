// server.js
import express from "express";
import fetch from "node-fetch";
import dotenv from "dotenv";
import cors from "cors";

dotenv.config();
const app = express();
app.use(cors());
app.use(express.json());

// 共通プロキシ関数
async function callOpenAI(path, body) {
  const res = await fetch(`https://api.openai.com${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": "Bearer "
    },
    body: JSON.stringify(body)
  });
  const data = await res.json();
  return data;
}

// 翻訳
add.post("/api/translate", async (req, res) => {
  const text = req.body.text;
  const response = await fetch("openai.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ 
      endpoint: "chat", 
      messages: [
      { role: "system", content: "You are a translation assistant." },
      { role: "user", content: `Translate this English sentence into Japanese:\n${sentence}` }
    ]
    })
  });
  const data = await response.json();
  res.json(data);
});



// 単語意味
app.post("/api/wordinfo", async (req, res) => {
  const { word } = req.body;
  const data = await fetch("openai.php", {
    method: "POST",
    headers:  { "Content-Type": "application/json" },
    body: JSON.stringify({
      messages: [
      { role: "system", content: "You are a dictionary-like assistant." },
      { role: "user", content: `Explain the meaning of the English word in brief Japanese but do not forget to explain many different defenitions of the word: ${word} in Japanese.` }
    ]
    })
  });
  res.json(data);
});

// TTS
app.post("/api/tts", async (req, res) => {
  const { text } = req.body;
  const response = await fetch("openai.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      endpoint: "audio_speech",
      voice: "alloy",
      input: text
    })
  });
  const buffer = await response.arrayBuffer();
  res.setHeader("Content-Type", "audio/mpeg");
  res.send(Buffer.from(buffer));
});

// 要約
app.post("/api/summaries", async (req, res) => {
  const { paragraph } = req.body;
  const data = await fetch("openai.php", {
    method: "POST",
    headers: {  "Content-Type": "application/json" },
    body: JSON.stringify({
      endpoint: "chat",
      messages: [
      {
        role: "system",
        content:
          "Summarize the paragraph in one English sentence. If it contains multiple important events, allow multiple sentences. " +
          "Provide both English and Japanese for each summary sentence. Respond in JSON with key 'summaries', which is an array of objects {english, japanese}."
      },
      { role: "user", content: paragraph }
    ]
    })
  });
  res.json(data);
});

// 画像生成
app.post("/api/image", async (req, res) => {
  const { prompt } = req.body;
  const data = await fetch("openai.php", {
    method: "POST",
    prompt,
    size: "auto"
  });
  res.json(data);
});


add.post("/api/image", async (req, res) => {
  const text = req.body.text;
  const response = await fetch("openai.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ 
      endpoint: "image",
    })
  });
  const data = await response.json();
  res.json(data);
});


app.listen(3000, () => console.log("✅ Server running on http://localhost:3000"));
