<?php
session_start();
require_once "api/pdo.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイムライン</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/timeline.css">
<style>
  .post { border-bottom: 1px solid #ccc; padding: 10px; }
  .time { color: gray; font-size: 0.9em; }
</style>
</head>
<body>
  <h2>みんなの学習タイムライン</h2>

<div style="margin: 10px 0;">
  <textarea id="postContent" placeholder="いまどうしてる？" rows="3" style="width:90%;"></textarea><br>
  <button id="postBtn">投稿する</button>
</div>
<hr>

  <div id="timeline"></div>

  <script>
    function loadTimeline(){
    // ここからは取得処理
  fetch("api/timeline_get.php")
      .then(res => res.json())
      .then(data => {
        if (data.status === "ok") {
          let html = "";
          data.posts.forEach(row => {
            html += `
              <div class="card post" style="margin:10px; padding:10px;">
              <b>${row.username}</b>さん<br>
              <p>${row.content}</p>
                <div class="time" style="font-size-small; color:#666;">
                ${row.created_at}
                </div>
              </div>
            `;
          });
          document.getElementById("timeline").innerHTML = html;
        } else {
          document.getElementById("timeline").textContent = "データ取得エラー";
        }
      })
      .catch(err => {
        console.error("APIエラー:", err);
        document.getElementById("timeline").textContent = "通信エラー";
      });
  }

    // ここからは投稿処理

  document.getElementById("postBtn").onclick = () => {
  const content = document.getElementById("postContent").value.trim();
  if(!content) return;

  fetch("api/timeline_post.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ content })
})
.then(res => res.json())
.then(data =>{
  if (data.status === "ok"){
    document.getElementById("postContent").value = "";
    loadTimeline();
  }else{
    alert(data.message);
  }
})
  .catch(err => console.error("投稿エラー:", err));
};
  
loadTimeline();
  </script>
</body>


<style>
  .footer-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #f0f0f0;
    border-top: 1px solid #ccc;
    display: flex;
    justify-content: space-around;
    padding: 10px 0;
  }
  .footer-nav button {
    flex: 1;
    border: none;
    background: none;
    font-size: 16px;
    cursor: pointer;
  }
  .footer-nav button:hover {
    background: #ddd;
  }
</style>

<footer class="footer-nav">
<button onclick="location.href='index.php'">HOME</button>
<button onclick="location.href='select.php'">VOCAB</button>
<button onclick="location.href='intermediate.html'">精読</button>
<button onclick="location.href='advanced.html'">速読</button>
<button onclick="location.href='timeline.php'">TIMELINE</button>
</footer>


</html>