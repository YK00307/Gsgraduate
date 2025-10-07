<?php
session_start();
require_once "api/pdo.php";

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LexiQuest - Home</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        table{
            border-collapse: collapse;
            margin-top: 10px;
            width: 50%
        }
        th, td{
            border: 1px solid;
            padding: 6px;
            text-align: center;
        }
    </style>
</head>
<body>
        <h2>ようこそ、<?= htmlspecialchars($username); ?> さん！</h2>
        <!-- <h3>ランキング</h3> -->
        <button onclick="location.href='login.php'">ログアウト</button>
        <!-- <div class="table-container">
        <div id="ranking"></div>
        <script>
            function loadRanking(){
                fetch("api/ranking.php")
                .then(res => res.json())
                .then(data => {
                    if (data.status === "ok") {
                        let html ="<table><tr><th>順位</th><th>ユーザー名</th><th>ポイント</th></tr>";
                        data.ranking.forEach((row, idx) =>{
                            html += `<tr>
                            <td>${idx+1}</td>
                            <td>${row.username}</td>
                            <td>${row.points}</td>
                        </tr>`;
                        });
                        html += "</table>";
                        document.getElementById("ranking").innerHTML = html;
                    } else {
                        document.getElementById("ranking").textContent = "ランキング取得エラー";
                    }
                })
                .catch(err => {
                    console.error("エラー:", err);
                    document.getElementById("ranking").textContent = "通信エラー";
                });
    }

    loadRanking(); -->
    <div id="content">
        <?php include("Mywords.html") ?>
    </div>

</script>
</div>

<h4>単語を押すと裏側に詳細ボタンがあります</h4>
<h4>詳細画面を閉じる際にはモーダル内ではなく画面右上の×ボタンを押すとHOMEに戻れます</h4>
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

</body>
</html>