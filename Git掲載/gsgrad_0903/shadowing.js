const formData = new FormData();
formData.append('message', 'Hello');
fetch('shadowing.php', {
  method: 'POST',
  body: formData // Content-Typeは自動付与されるため、指定不要
}).then(res => res.json()).then(data => {
  console.log(data);
});


let mediaRecorder;
let audioChunks = [];

const recordBtn = document.getElementById("recordBtn");
const stopRecordBtn = document.getElementById("stopRecordBtn");
const recordingsList = document.getElementById("recordingsList");

recordBtn.addEventListener("click", async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

    mediaRecorder = new MediaRecorder(stream);
    audioChunks = [];

    mediaRecorder.ondataavailable = e => {
      if (e.data.size > 0) {
        audioChunks.push(e.data);
      }
    };

    mediaRecorder.onstop = () => {
      const audioBlob = new Blob(audioChunks, { type: "audio/webm" });
      const url = URL.createObjectURL(audioBlob);

      // 日付と時間で名前をつける
      const now = new Date();
      const fileName = `録音.${now.toLocaleDateString()}.${now.toLocaleTimeString()}`;

      // カードを作成
      const card = document.createElement("div");
      card.style.border = "1px solid #ccc";
      card.style.padding = "10px";
      card.style.marginBottom = "10px";

      card.innerHTML = `
        <p><strong>${fileName}</strong></p>
        <audio controls src="${url}"></audio>
      `;

      recordingsList.appendChild(card);
    };

    mediaRecorder.start();
    console.log("録音開始");

    // // ボタン切り替え
    // recordBtn.disabled = true;
    // stopBtn.disabled = false;

  } catch (err) {
    console.error("マイク起動エラー:", err);
    alert("マイクが利用できません");
  }
});

stopRecordBtn.addEventListener("click", () => {
  if (mediaRecorder && mediaRecorder.state === "recording") {
    mediaRecorder.stop();
    console.log("録音停止");
  }
  // recordBtn.disabled = false;
  // stopBtn.disabled = true;
});

// 文字起こしリクエスト
fetch('openai.php', {
    method: 'POST',
    body: formData // multipart form で音声ファイルとendpoint: 'transcription'を送信
}).then(res => res.json())
.then(data => {
    const transcribedText = data.text;
    // 次にchat APIへ評価のために送る
    sendChatEvaluation(transcribedText);
});

function sendChatEvaluation(text) {
    const messages = [
        { role: 'system', content: 'あなたは英語発音の先生です。' },
        { role: 'user', content: '以下の発音について評価してください：' + text }
    ];
    fetch('openai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ endpoint: 'chat', messages: messages })
    }).then(res => res.json())
    .then(data => {
        // 評価結果を表示
        console.log(data.choices[0].message.content);
    });
}


