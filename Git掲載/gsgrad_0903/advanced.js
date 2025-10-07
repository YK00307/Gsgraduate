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
    if (!inputText) {
    alert("英文を入力してください");
    return;
    }
renderOutput(inputText);
});


// 出力処理
function renderOutput(text) {
    const output = document.getElementById("outputArea");
    output.innerHTML = ""; // 一旦クリア
    output.textContent = text; // テキストとして表示
}

// ② イメージ図生成（全体ベース）
document.getElementById("illustrateBtn").addEventListener("click", async () => {
  const text = document.getElementById("inputText").value.trim();
  if (!text) {
    alert("テキストを入力してください");
    return;
  }

  const container = document.getElementById("illustrationArea");
  container.innerHTML = "<p>画像生成中...</p>";

  try {
    const imgUrl = await generateImage(text);
    container.innerHTML = `<h3>全体イメージ</h3><img src="${imgUrl}" style="max-width:80%; margin:10px;">`;
  } catch (err) {
    console.error("画像生成エラー:", err);
    container.innerHTML = "<p>画像生成に失敗しました。</p>";
  }
});


// ③ パラグラフ要約（イラストなし）
document.getElementById("paragraphSummaryBtn").addEventListener("click", async () => {
  const text = document.getElementById("inputText").value.trim();
  if (!text) {
    alert("テキストを入力してください");
    return;
  }

  const paragraphs = text.split(/\n+/);
  const container = document.getElementById("illustrationArea");
  container.innerHTML = "<h3>要約一覧</h3>";

  for (let i = 0; i < paragraphs.length; i++) {
    const p = paragraphs[i];
    if (!p.trim()) continue;

    // 要約を生成
    const summaries = await generateSummaries(p);

    // 要約を表示
    const summaryEl = document.createElement("div");
    summaryEl.innerHTML = `<h4>Paragraph ${i + 1}</h4><ul></ul>`;
    const ul = summaryEl.querySelector("ul");

    for (let s of summaries) {
      let li = document.createElement("li");
      li.innerText = `${s.english} / ${s.japanese}`;
      ul.appendChild(li);
    }

    container.appendChild(summaryEl);
  }
});


// 要約生成
async function generateSummaries(paragraph) {
  const response = await fetch("openai.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      endpoint: "chat",
      messages: [
        {
          role: "system",
          content:
            "Summarize the paragraph in one English sentence. If it contains multiple important events, allow multiple sentences. However, ONLY write something important and omit something unimportant. " +
            "Provide both English and Japanese for each summary sentence. Respond in JSON with key 'summaries', which is an array of objects {english, japanese}."
        },
        { role: "user", content: paragraph }
      ]
    })
  });

  const data = await response.json();
  const raw = data.choices[0].message.content;
  try {
    return JSON.parse(raw).summaries;
  } catch {
    return [{ english: raw, japanese: "翻訳エラー" }];
  }
}

async function generateImage(prompt) {
const response = await fetch("openai.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    endpoint: "image",
    prompt: prompt,
    size: "1792x1024"
  })
});

// レスポンス内容を生で確認
const text = await response.text();
console.log("サーバーからの生レスポンス:", text);
let data;
try{
  data = JSON.parse(text);
}catch(e){
  throw new Error("サーバーから不正なレスポンスが、、"+ text);
}

  if (!data.data || data.data.length === 0) {
    throw new Error(data.error?.message || "画像が返されませんでした");
  }

  return data.data[0].url;
}