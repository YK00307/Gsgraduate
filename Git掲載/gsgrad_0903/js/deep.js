let words = [];
let currentWord = null;
let usedWordIds = [];
let mode ="mcq";
const currentUserId= 1;

// --- 最初に単語を読み込む ---
window.onload = () => {
    const params = new URLSearchParams(window.location.search);
    const wordId = params.get("wordId");

    loadWord().then(() => {
        if(wordId){
// 1問目はURL指定の単語
        const firstWord = words.find(w => String(w.id) === String(wordId));
        if(firstWord){
        currentWord = firstWord;
        renderCard();
        usedWordIds.push(String(firstWord.id));
        }else{
            console.warn("指定されたwordIdが存在しません", wordId);
        nextWord();
        }
    }else{
        // URL設定なしならランダム
        nextWord();
    }
});
};

// --- 単語リストを読み込む ---
// async function loadWords(startWordId = null) {
//     const params = new URLSearchParams(window.location.search);
//     const level = params.get("level") || "";
//     const stage = params.get("stage") || 1;
//     const chapter = params.get("chapter") || 1;

//     try {
//         const res = await fetch(`/卒業制作0903/api/get_words.php?level=${level}&stage=${stage}&chapter=${chapter}`);
//         const data = await res.json();

//         if (data.ok && data.items.length > 0) {
//             words = data.items;
//             usedWordIds = [];

//             if (startWordId) {
//                 // まずは指定された単語から出題
//                 currentWord = words.find(w => w.id == startWordId) || null;
//                 if (currentWord) {
//                     usedWordIds.push(currentWord.id);
//                     renderCard();
//                 } else {
//                     // 万が一見つからなければランダムで開始
//                     nextWord();
//                 }
//             } else {
//                 // 最初からランダム
//                 nextWord();
//             }
//         } else {
//             console.warn("データが空です:", data);
//         }
//     } catch (err) {
//         console.error("APIエラー:", err);
//     }
// }

function nextWord() {
    if (!words || words.length === 0) {
        console.error("words が空です！");
        return;
    }
    if (usedWordIds.length >= words.length) {
        alert("全問出題完了！もう一度始めます。");
        usedWordIds = [];
    }

    let candidate;
    let loopCount = 0;
    do {
        candidate = words[Math.floor(Math.random() * words.length)];
        loopCount++;
        if (loopCount > 100) {
            console.error("無限ループの可能性", words);
            return;
        }
    } while (usedWordIds.includes(candidate.id));

    currentWord = candidate;
    usedWordIds.push(candidate.id);
    renderCard();
}


// --- 特定の単語を読み込む ---
async function loadWordById(wordId) {
    try {
        const res = await fetch(`/卒業制作0903/api/get_word.php?id=${wordId}`);
        const data = await res.json();
        if (data.ok && data.word) {
            currentWord = {
                ...data.word,
                choices: data.choices || []
            };
            renderCard();
        }
    } catch (err) {
        console.error("APIエラー:", err);
    }
}

// 問題を表示
async function renderCard(){
    // 関数の定義
    const qDiv = document.getElementById("question");
    const ansInput = document.getElementById("answerInput");
    const choices = document.getElementById("choices");
    const feedback = document.getElementById("feedback");
    const warning = document.getElementById("warning");

    // htmlで非表示にしたものを非表示にする
    feedback.style.display = "none";
    warning.style.display = "none";
    ansInput.style.display = "none";
    choices.style.display = "none";

    if(!currentWord) return;

    // 選択肢の場合
    if(mode === "mcq"){
        // 問題文の該当単語のみを空欄にすり替える
        qDiv.textContent = currentWord.example_en.replace(currentWord.word_en, "_____");
        // 選択肢の表示
        choices.style.display = "block";
        // 選択肢の描画を担当する関数
        renderChoices();
    }else if (mode === "fill"){
        qDiv.textContent = currentWord.example_en.replace(currentWord.word_en, "_____");
        // 入力欄の表示
        ansInput.style.display = "block";
    }
}

// 選択肢の描画を担当する関数を書いていく
function renderChoices(){
    const choicesDiv = document.getElementById("choices");
    choicesDiv.innerHTML = "";

    currentWord.choices.forEach((c) => {
        const btn = document.createElement("button");
        btn.textContent = c;
        btn.onclick = () => {
            disableChoices();
            checkAnswer(c);
        };
        choicesDiv.appendChild(btn);
    });
}

function disableChoices(){
    const buttons = document.querySelectorAll("#choices button").forEach(btn => btn.disabled = true);
}

function checkAnswer(choice = null){
    let correct = false;

    if (mode === "mcq") {
        correct = (choice === currentWord.word_en);
    }
    if (mode === "fill") {
        const userAns = document.getElementById("answerInput").value.trim();
        correct = (userAns.toLowerCase() === currentWord.word_en.toLowerCase());
    }

    fetch('/卒業制作0903/api/mastery.php', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            userId: currentUserId,
            wordId: currentWord.id,
            result: correct ? "correct" : "incorrect",
            mode: mode
        })
    })
    .then(res => res.json())
    .then(data => {
        showFeedback(correct ? `正解！ +${data.points}pt` : "不正解...");
        if (!correct && currentWord.mistake_count >= 2) {
            showWarning("前回も間違えた単語です！！");
        }
    });
}

// --- フィードバックを表示する関数 ---
function showFeedback(message) {
    const feedback = document.getElementById("feedback");
    feedback.style.display = "block";
    feedback.textContent = message;
}

// --- 警告を表示する関数 ---
function showWarning(message) {
    const warning = document.getElementById("warning");
    warning.style.display = "block";
    warning.textContent = message;
}

// 答えるボタンを押したときの処理
// →ボタンは押されたボタンで決めるが、記述式はテキストボックスに書かれたもので決めるのだということを書いている
document.getElementById("checkBtn").addEventListener("click", () =>{
    if(mode ==="fill")checkAnswer();
});

document.getElementById("nextBtn").addEventListener("click", nextWord);

// 選択肢のボタンが押されたら画面をそちらに合わせて問題を再表示させる
document.getElementById("btn-mcq").addEventListener("click", () => {
    mode = "mcq";
    renderCard();
});

// 選択肢のボタンが押されたら画面をそちらに合わせて問題を再表示させる
document.getElementById("btn-fill").addEventListener("click", () => {
    mode = "fill";
    renderCard();
});

const urlParams = new URLSearchParams(window.location.search);
const wordId = urlParams.get("wordId");
