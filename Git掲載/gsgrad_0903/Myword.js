const container = document.getElementById("mywordContainer");
let currentGeneratedWord = null;

// ページロード時にDBから単語を取得
document.addEventListener("DOMContentLoaded", () => {
  loadMyWordsFromDB();
});

// DBから取得して描画
function loadMyWordsFromDB() {
  fetch("get_mywords.php")
    .then(res => res.json())
    .then(data => {
      renderWords(data);
      localStorage.setItem("myWords", JSON.stringify(data));
    })
    .catch(err => console.error("DB取得エラー:", err));
}

// 単語カード描画
function renderWords(wordsArray) {
  container.innerHTML = "";
  wordsArray.forEach(item => {
    const card = document.createElement("div");
    card.className = "word-card";

    const inner = document.createElement("div");
    inner.className = "card-inner";

    const front = document.createElement("div");
    front.className = "card-front";
    front.innerText = item.word;

    const back = document.createElement("div");
    back.className = "card-back";
    back.innerHTML = `
      <p>${item.short_meaning || "..."}</p>
      <button class="detail-btn">詳細を見る</button>
    `;

    // 詳細ボタンイベント
    back.querySelector(".detail-btn").addEventListener("click", e => {
      e.stopPropagation();
      showDetail(item);
    });

    inner.appendChild(front);
    inner.appendChild(back);
    card.appendChild(inner);

    // カードクリックで裏返す
    card.addEventListener("click", () => card.classList.toggle("flipped"));
    container.appendChild(card);
  });
}

// 詳細モーダル表示
function showDetail(item) {
  currentGeneratedWord = item; // 現在表示している単語を保存

  document.getElementById("modalWord").innerText = item.word;
  document.getElementById("modalShortMeaning").innerText = item.short_meaning || "";
  document.getElementById("modalMeaning").innerText = item.meaning || "";
  document.getElementById("modalSentence").innerText = item.sentence || "";

  document.getElementById("wordModal").style.display = "block";
}

// モーダル閉じる
document.getElementById("detailClose").addEventListener("click", () => {
  document.getElementById("wordModal").style.display = "none";
});
document.getElementById("addClose").addEventListener("click", () => {
  document.getElementById("addModal").style.display = "none";
});

// // 新規追加モーダルを開く
// document.getElementById("addBtn").addEventListener("click", () => {
//   document.getElementById("addWordInput").value = "";
//   document.getElementById("addModal").style.display = "block";
// });


// 作成ボタンが押される
document.getElementById("createBtn").addEventListener("click", async () => {
  const word = document.getElementById("addWordInput").value.trim();
  if (!word) return alert("単語を入力してください");

  try {
    const res = await fetch("generate_word.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ word })
    });

    const text = await res.text();
    console.log("サーバー生レス:", text);

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error("JSON解析失敗。サーバーから返ってきたのはHTMLかも:", text);
      return;
    }

    if (!data.success) {
      alert("生成失敗: " + data.message);
      return;
    }

    // モーダルにセット
    currentGeneratedWord = {
      word: word,
      short_meaning: data.data?.shortMeaning ?? "",
      meaning: data.data?.meaning ?? "",
      sentence: data.data?.sentence ?? ""
    };

    document.getElementById("modalWord").innerText = word;
    document.getElementById("modalShortMeaning").innerText = currentGeneratedWord.short_meaning;
    document.getElementById("modalMeaning").innerText = currentGeneratedWord.meaning;
    document.getElementById("modalSentence").innerText = currentGeneratedWord.sentence;

    document.getElementById("wordModal").style.display = "block";
    document.getElementById("addModal").style.display = "none";

  } catch (err) {
    console.error("通信エラー:", err);
  }
});



// モーダル内「My単語帳に追加」ボタン
document.getElementById("addToMyWordsBtn").addEventListener("click", () => {
  if (!currentGeneratedWord) return;

  const item = currentGeneratedWord;
  const cardHTML = `
    <div class="word-card">
      <div class="card-inner">
        <div class="card-front"><p class="word-text">${item.word}</p></div>
        <div class="card-back">
          <p>${item.short_meaning}</p>
          <button class="detail-btn">詳細を見る</button>
        </div>
      </div>
    </div>
  `;
  container.insertAdjacentHTML("beforeend", cardHTML);

  // detailボタン再設定
  const newCard = container.lastElementChild;
  newCard.querySelector(".detail-btn").addEventListener("click", () => showDetail(item));

  document.getElementById("wordModal").style.display = "none";
});

document.getElementById("markLearnedBtn").addEventListener("click", () => {
  fetch("update_word_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ word: currentGeneratedWord.word, status: 1 })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("覚えた！にしました");
      document.getElementById("wordModal").style.display = "none";
      loadMyWordsFromDB(); // 再描画
    }
  });
});
