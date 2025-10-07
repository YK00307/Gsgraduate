<?php
// =======================
// DB接続
// =======================
$dsn = 'mysql:host=mysql3109.db.sakura.ne.jp;dbname=rivside_gsgrad_example;charset=utf8mb4';
$user = 'rivside_gsgrad_example';
$password = '';
$pdo = new PDO($dsn, $user, $password);


// ユーザーID仮
$user_id = 1;

// レベル選択
$exam_level = isset($_GET['level']) ? $_GET['level'] : 'pre1';

// =======================
// ステージ進捗取得
// =======================
$sqlStage = "
    SELECT 
        w.stage_id,
        IFNULL(SUM(CASE WHEN uw.mastered = 1 THEN 1 ELSE 0 END) * 100 / COUNT(uw.word_id), 0) AS progress
    FROM words_stages_complete w
    LEFT JOIN user_words uw ON w.id = uw.word_id AND uw.user_id = :user_id
    WHERE w.exam_level = :exam_level
    GROUP BY w.stage_id
    ORDER BY w.stage_id;
";
$stmt = $pdo->prepare($sqlStage);
$stmt->execute([
    ':user_id' => $user_id,
    ':exam_level' => $exam_level
]);
$stageProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// チャプター進捗を取得
// =======================
$sqlChapter = "
    SELECT 
        w.stage_id,
        w.chapter_id,
        IFNULL(SUM(CASE WHEN uw.mastered = 1 THEN 1 ELSE 0 END) * 100 / COUNT(uw.word_id), 0) AS progress
    FROM words_stages_complete w
    LEFT JOIN user_words uw ON w.id = uw.word_id AND uw.user_id = :user_id
    WHERE w.exam_level = :exam_level
    GROUP BY w.stage_id, w.chapter_id
    ORDER BY w.stage_id, w.chapter_id;
";
$stmt = $pdo->prepare($sqlChapter);
$stmt->execute([
    ':user_id' => $user_id,
    ':exam_level' => $exam_level
]);
$chapterProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ステージごとにチャプターをまとめ直す
$chaptersByStage = [];
foreach ($chapterProgress as $row) {
    $chaptersByStage[$row['stage_id']][] = $row;
}

// 進捗色関数
function getColor($progress) {
    if ($progress >= 100) return "background-color:#ff0000;color:white;";
    if ($progress >= 50)  return "background-color:#ff6666;color:white;";
    if ($progress > 0)    return "background-color:#ffcccc;color:black;";
    return "background-color:#ffffff;color:black;";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>学習セレクト</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  h1 { text-align:center; }
  .level-selector { text-align:center; margin-bottom:30px; }
  .level-selector a { padding:10px 20px; border:1px solid #333; text-decoration:none; margin:0 10px; }
  .level-selector a.active { background:#eee; }
  .stage-container { display:flex; flex-wrap:wrap; justify-content:center; }
  .stage-box, .chapter-box {
    width:150px; height:100px; border:1px solid #ccc; border-radius:10px;
    margin:10px; display:flex; flex-direction:column; justify-content:center; align-items:center;
    cursor:pointer; transition:transform .2s;
  }
  .stage-box:hover, .chapter-box:hover { transform:scale(1.05); }
  .progress { margin-top:10px; padding:5px; border-radius:5px; }
  .chapter-container { display:none; flex-wrap:wrap; justify-content:center; }
</style>
<script>
// ステージクリックでチャプターを展開
function toggleChapters(stageId) {
    document.querySelectorAll('.chapter-container').forEach(div => div.style.display='none');
    document.getElementById('chapters-stage-' + stageId).style.display = 'flex';
}
</script>
</head>
<body>
<h1>学習セレクト</h1>
<h3>レベルとステージとチャプターを選ぼう！！</h3>

<!-- レベル選択 -->
<div class="level-selector">
  <a href="?level=pre1" class="<?php echo ($exam_level=='pre1')?'active':''; ?>">英検準1級</a>
  <a href="?level=1" class="<?php echo ($exam_level=='1')?'active':''; ?>">英検1級</a>
</div>

<!-- ステージ一覧 -->
<div class="stage-container">
  <?php foreach ($stageProgress as $row): ?>
    <div class="stage-box" onclick="toggleChapters(<?php echo $row['stage_id']; ?>)">
      <span>Stage <?php echo $row['stage_id']; ?></span>
      <div class="progress" style="<?php echo getColor((int)$row['progress']); ?>">
        <?php echo (int)$row['progress']; ?>%
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- チャプター一覧（ステージごとにまとめて表示用） -->
<?php foreach ($chaptersByStage as $stageId => $chapters): ?>
  <div id="chapters-stage-<?php echo $stageId; ?>" class="chapter-container">
    <?php foreach ($chapters as $ch): ?>
      <div class="chapter-box"
           onclick="location.href='slide.html?stage=<?php echo $stageId; ?>&chapter=<?php echo $ch['chapter_id']; ?>&level=<?php echo $exam_level; ?>'">
        <span>Chapter <?php echo $ch['chapter_id']; ?></span>
        <div class="progress" style="<?php echo getColor((int)$ch['progress']); ?>">
          <?php echo (int)$ch['progress']; ?>%
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<h3>追記10/06：この先、学習画面が登場する予定でしたが500エラーが起こってしまい、現在は単語とその音声しか表示されません。</h3>
<h3>深く学習モードにつきましても復旧中です。恐れ入ります。</h3>

</body>
</html>

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