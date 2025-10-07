// イベント登録
document.getElementById("hideAllBtn").addEventListener("click", hideAllTranslations);

// シャドーイング
let listeMmediaRecorder;
let listenAudioChunks = [];


// OCRから読み取った結果をテキストエリアに表示
document.getElementById("fileInputOCR").addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (!file) return;

  // 画像ファイルならTesseract
    if (/\.(png|jpg|jpeg|gif)$/i.test(file.name)) {
    Tesseract.recognize(file, 'eng', { logger: m => console.log(m) })
        .then(({ data: { text } }) => {
        currentText = text;
        document.getElementById("fileTextArea").innerText = text;
        document.getElementById("inputText").value = text; 
        });
    } else {
    // テキストファイルの場合
    const reader = new FileReader();
    reader.onload = e => {
        currentText = e.target.result;
        document.getElementById("fileTextArea").innerText = currentText;
        document.getElementById("inputText").value = currentText;
    };
    reader.readAsText(file);
    }
});

// 抽出ボタンが押されたとき
document.getElementById("extractBtn").addEventListener("click", () => {
    const inputText = document.getElementById("inputText").value.trim();
    const output = document.getElementById("outputArea");
    if (!inputText) {
    alert("英文を入力してください");
    return;
    }

  // 文ごとに分割（ピリオド、疑問符、改行など）
    const sentences = inputText.split(/(?<=[.?!])\s+/);

  output.innerHTML = ""; // 一旦クリア

    sentences.forEach((sentence, index) => {
    if (sentence.trim()) {
        const block = document.createElement("div");
        block.className = "sentence-block";

        const eng = document.createElement("p");
        eng.className = "english-sentence";
        eng.textContent = sentence;

        const btn = document.createElement("button");
        btn.textContent = "訳を表示";
        btn.className = "toggle-translation";

        const jp = document.createElement("p");
        jp.className = "translation hidden";
        jp.textContent = ""; // ここに後で訳を入れる

        block.appendChild(eng);
        block.appendChild(btn);
        block.appendChild(jp);

        output.appendChild(block);
    }   renderOutput(sentences);
    });
});


// 出力処理
async function renderOutput(sentences) {
    const output = document.getElementById("outputArea");
    output.innerHTML = "";

    for (let i = 0; i < sentences.length; i++) {
    const sentence = sentences[i];

    // 文全体を包むdiv
    const div = document.createElement("div");
    div.classList.add("sentence-block");

    // 英文（単語ごとにspan化）
    const engLine = document.createElement("p");
    sentence.split(/\s+/).forEach(w => {
        const span = document.createElement("span");
        span.innerText = w + " ";
        span.style.cursor = "pointer";
        span.addEventListener("click", () => showWordDetail(w, sentence));
        engLine.appendChild(span);
    });
    div.appendChild(engLine);
    
    // 文ごとに読み上げよう
    const playBtn = document.createElement("button");
    playBtn.innerText = "🔊 読み上げ";
    playBtn.addEventListener("click", () => {
    generateSentenceTTS(sentence);
    });
    engLine.appendChild(playBtn);

    // 翻訳用の<p>
    const jpLine = document.createElement("p");
    jpLine.style.display = "none"; // 初期は非表示
    div.appendChild(jpLine);
    console.log("96");

    // ボタン
    const toggleBtn = document.createElement("button");
    toggleBtn.innerText = "訳を表示";
    toggleBtn.addEventListener("click", async () => {
        console.log("ボタンが押されました", sentence);
        
        if (jpLine.style.display === "none") {
        // 訳が非表示 → 表示する
        if (!jpLine.dataset.loaded) {
            jpLine.innerText = "翻訳中...";
            const translation = await translateSentence(sentence);
            jpLine.innerText = translation;
          jpLine.dataset.loaded = "true"; // 1度取得したら保持
        }
        jpLine.style.display = "block";
        toggleBtn.innerText = "訳を隠す";
        } else {
        // 訳が表示中 → 隠す
        jpLine.style.display = "none";
        toggleBtn.innerText = "訳を復元";
        }
    });
    div.appendChild(toggleBtn);

    // 出力領域に追加
    output.appendChild(div);
    }
}

// 翻訳作業
async function translateSentence(sentence) {
    try {
    const res = await fetch("openai.php", {
        method: "POST",
        headers: {
        "Content-Type": "application/json",
        },
        body: JSON.stringify({
        endpoint: "chat",
        messages: [
            { role: "system", content: "You are a helpful assistant that translates English into natural Japanese." },
            { role: "user", content: `Translate this English sentence into Japanese:\n${sentence}` }
        ]
        })
    });

    const data = await res.json();

    if (data.error) {
        console.error("翻訳APIエラー:", data.error);
        return "[翻訳失敗]";
    }

    return data.choices[0].message.content.trim();
    } catch (err) {
    console.error("通信エラー:", err);
    return "[翻訳エラー]";
    }
}


async function generateSentenceTTS(sentence) {
  console.log("TTS開始:", sentence);
  const response = await fetch("openai.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      endpoint: "audio_speech",
      voice: "alloy",
      text: sentence,
    })
  });

  if (!response.ok) {
    console.error("TTS API Error:", await response.text());
    return;
  }

  const arrayBuffer = await response.arrayBuffer();
  const blob = new Blob([arrayBuffer], { type: "audio/mpeg" });
  const url = URL.createObjectURL(blob);

  const audio = new Audio(url);
  audio.play();
}



// 単語詳細モーダル
async function showWordDetail(word, sentence) {
  const wordEl = document.getElementById("detailWord");
  const sentenceEl = document.getElementById("detailSentence");
  const modalEl = document.getElementById("detailModal");
  const meaningEl = document.getElementById("detailMeaning");

  if (!wordEl || !sentenceEl || !modalEl || !meaningEl) {
    console.error("モーダル要素が見つかりません");
    return;
  }

  wordEl.innerText = word;
  sentenceEl.innerText = sentence;
  modalEl.style.display = "block";

  const meaning = await getWordInfo(word);
  meaningEl.innerText = meaning;
}

// 単語の意味を取得
async function getWordInfo(word) {
  const res = await fetch("openai.php", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({
        endpoint: "chat",
        messages: [
        { role: "system", content: "You are a dictionary-like assistant." },
        { role: "user", content: `Explain the meaning of the English word in brief Japanese but do not forget to explain many different defenitions of the word: ${word} in Japanese.` }
      ]
    })
  });
  const data = await res.json();
  console.log(data);
  return data.choices[0].message.content.trim();
}

// モーダル閉じる
document.getElementById("closeModal").addEventListener("click", () => {
  document.getElementById("detailModal").style.display = "none";
});

// Mywordに追加
function addToMyWords(word, meaning, sentence, shortMeaning) {
  console.log("addToMyWords実行:", word, meaning, sentence, shortMeaning);

  // localStorage 保存
  let myWords = JSON.parse(localStorage.getItem("myWords")) || [];
  if (!myWords.some(w => w.word === word)) {
    myWords.push({ word, meaning, sentence, shortMeaning });
    localStorage.setItem("myWords", JSON.stringify(myWords));
    alert(`${word} をMy単語帳に追加しました！（ローカル保存）`);

    // サーバーにも保存
    fetch("save_word.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ word, meaning, sentence, shortMeaning })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        console.log("DBに保存成功:", data);
        loadMyWords();
      } else {
        console.error("DB保存エラー:", data.message);
      }
    })
    .catch(err => {
      console.error("通信エラー:", err);
    });

  } else {
    alert(`${word} はすでにMy単語帳にあります。`);
  }
}

// My単語帳に追加ボタン
document.getElementById("AddToMyWordBtn").addEventListener("click", () => {
  const word = document.getElementById("detailWord").innerText;
  const meaning = document.getElementById("detailMeaning").innerText;
  const sentence = document.getElementById("detailSentence").innerText;

  addToMyWords(word, meaning, sentence, "");
});


// 一括で訳を隠す
function hideAllTranslations() {
  console.log("すべての訳を隠します");

  // outputAreaの中にある翻訳<p>を全部取得
  const translations = document.querySelectorAll("#outputArea .sentence-block p:nth-of-type(2)");
  translations.forEach(jpLine => {
    jpLine.style.display = "none";
  });

  // 各ボタンも「訳を復元」に戻す
  const buttons = document.querySelectorAll("#outputArea .sentence-block button");
  buttons.forEach(btn => {
    btn.innerText = "訳を復元";
  });
}


// 録音開始
document.getElementById("recordBtn").addEventListener("click", async () => {
  const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
  mediaRecorder = new MediaRecorder(stream);

  audioChunks = [];
  mediaRecorder.ondataavailable = e => audioChunks.push(e.data);

  mediaRecorder.onstop = () => {
  const blob = new Blob(audioChunks, { type: "audio/webm" });
  const url = URL.createObjectURL(blob);
  const audioEl = document.createElement("audio");
  audioEl.src = url;
  audioEl.controls = true;
  document.getElementById("recordingList").appendChild(audioEl);

  console.log("録音完了:", url);
  document.getElementById("sendBtn").disabled = false;

  };

  mediaRecorder.start();
  console.log("録音開始");
});

document.getElementById("recordBtn").disabled = true;
document.getElementById("recordStopBtn").disabled = false;


// 録音停止
document.getElementById("recordStopBtn").addEventListener("click", () => {
  if (mediaRecorder && mediaRecorder.state !== "inactive") {
    mediaRecorder.stop();
    console.log("録音停止");
  }
});

