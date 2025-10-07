// 以下コンソール実施
console.log("prev button:", document.getElementById("prev"));
console.log("play button:", document.getElementById("play"));
console.log("next button:", document.getElementById("next"));
// コンソールおわり

let timer = null;
let playing = false;
let words = [];
let currentIndex = 0;

window.onload = () => {
    loadWords();
};

async function loadWords(){
    const params = new URLSearchParams(window.location.search);
    const level = params.get("level")||"pre1";
    const stage = params.get("stage")||1;
    const chapter = params.get("chapter")||1;


    try{
    const res = await fetch(`https://rivside.sakura.ne.jp/gsgrad_0903/api/get_words.php?level=${level}&stage=${stage}&chapter=${chapter}`);
    const data = await res.json();
    // 以下コンソールログ実施
    console.log("API result:", data);
    // ログおわり
    if(data.ok && data.items.length > 0){
    words = data.items;
    currentIndex = 0;
    showCurrentWord();
    }else{
        console.warn("データが存在しません");
    }
} catch(err) {
    console.error("APIエラー：", err);
}}


function showCurrentWord(){
    if(!words[currentIndex]) return;
    const w = words[currentIndex];
    document.getElementById('word').textContent = w.word_en;
    document.getElementById('meaning').textContent = `${w.word_jp} ${w.word_def || ""}`;
    document.getElementById('example-en').textContent = w.example_en;
    document.getElementById('example-jp').textContent = w.example_jp;
    // document.getElementById('progress').textContent = `${currentIndex+1}/${words.length}`;
    // 音量ゲージ
    document.getElementById('bar').value = (currentIndex+1) / words.length * 100;
    speak(w.word_en);
// renderが２回定義されている
// 後のほうに引っ張られてしまうので要注意
}
// [関数の確認：items,words,currentIndex,showCurrentIdとはそれぞれ何を指す？どこに生きるの？]

document.getElementById("prev").addEventListener("click", () => {
    currentIndex--;
    if(currentIndex < 0){
        currentIndex = words.length - 1;
    }
    showCurrentWord();
});

document.getElementById("next").addEventListener("click", () => {
    currentIndex++;
    if(currentIndex >= words.length){
        currentIndex = 0;
    }
    showCurrentWord();
});


// 再生開始
function start(){
    if(playing) return;
    playing = true;
    const interval = parseInt(document.getElementById("speed").value, 10) * 1000;
    timer = setInterval(() =>{
        document.getElementById("next").click();
    },interval);
    document.getElementById("play").textContent = "⏸";
    }


function stop(){
    playing = false;
    clearInterval(timer);
    document.getElementById("play").textContent ="▶";
}

document.getElementById("play").addEventListener("click", () => {
    playing ? stop() : start();
});


// 音声
function speak(text){
    if(!text) return;
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "en-US";
    speechSynthesis.speak(utterance);
}

// DOM構築後にまとめて実行
document.addEventListener("DOMContentLoaded", () => {

  // 深く学習への遷移
  const deepBtn = document.getElementById("deepBtn");
  if (deepBtn) {
    deepBtn.addEventListener("click", () => {
      if (!words.length) return;
      const w = words[currentIndex];
      window.location.href = `deep.html?level=${level}&stage=${stage}&chapter=${chapter}&startWordId=${w.id}`;
    });
  };

document.getElementById('generateBtn').addEventListener('click', async () => {
    try {
        const res = await fetch('generate_word_info.php', { /* ... */ });

        // HTTPエラー（500）でもJSONパースを試みる
        const data = await res.json(); 

        if (data.success === false) {
            // PHP側でキャッチされたエラー情報が表示される！
            console.error('PHP処理エラー:', data.message);
            console.error('致命的エラー発生:', data.error_detail);
            console.error('発生場所:', `${data.error_file} の ${data.error_line}行目`);
        } else {
            console.log('データの生成と保存が完了しました。');
          alert(`${w.word_en} の情報を登録しました`);
        }

    } catch (e) {
        // ここに到達した場合は、まだPHPがJSONを返せていない（未定義変数など）か、
        // ネットワークの問題です。
        console.error('最終的な通信またはパースエラー:', e);
    }
});
})

  // 情報生成ボタン
//   const generateBtn = document.getElementById("generateBtn");
//   if (generateBtn) {
//     generateBtn.addEventListener("click", async () => {
//       if (!words || words.length === 0) return alert("単語がありません");

//       for (const w of words) {
//         if (w.word_jp) continue; // 登録済みならスキップ

//         try {
//           const res = await fetch("https://rivside.sakura.ne.jp/gsgrad_0903/generate_word_info.php", {
//             method: "POST",
//             headers: { "Content-Type": "application/json" },
//             body: JSON.stringify({ word: w.word_en })
//           });

//           const data = await res.json();

//           if (res.ok && data.success) {
//             alert(`${w.word_en} の情報を登録しました`);
//           } else if (data.message?.includes("すでに保管されています")) {
//             alert(`${w.word_en} はすでに保管されています`);
//           } else {
//             console.warn(`${w.word_en} 生成失敗:`, data.error);
//             alert(`${w.word_en} の情報生成に失敗しました: ${data.error}`);
//           }

//         } catch (error) {
//           console.error(`${w.word_en} 生成時に通信エラーが発生しました`, error);
//           alert(`${w.word_en} の情報生成に通信エラーが発生しました`);
//         }
//       }
//     });
//   }

//   console.log("generateBtn:", generateBtn);
// });

