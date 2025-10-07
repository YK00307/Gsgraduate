<?php
// エラー表示設定（開発用） 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/api/pdo.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ログインか新規登録かを判定
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = 'ユーザー名かパスワードが違います';
            }
        } else {
            $error = 'ユーザー名とパスワードを入力してください';
        }
    }

    // 新規登録
    if (isset($_POST['register'])) {
        $newUsername = $_POST['new_username'] ?? '';
        $email = $_POST['email'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if ($newUsername && $email && $newPassword) {
            // すでに同じユーザー名があるか確認
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$newUsername]);
            if ($stmt->fetch()) {
                $error = 'そのユーザー名はすでに使われています';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$newUsername, $email, $hash]);
                $success = '新規登録が完了しました。ログインしてください。';
            }
        } else {
            $error = '全ての項目を入力してください';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<link rel="stylesheet" href="login.css">

<title>ログイン</title>

</head>
<body>
    <h2>ログイン</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label>ユーザー名:
            <input type="text" name="username" required>
        </label><br>
        <label>パスワード:
            <input type="password" name="password" required>
        </label><br>
        <button type="submit" name="login">ログイン</button>
    </form>

    <br>
    <button id="openModal">新規登録はこちら</button>

    <!-- 新規登録モーダル -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h3>新規登録</h3>
            <form method="post" action="">
                <label>ユーザー名:
                    <input type="text" name="new_username" required>
                </label><br>
                <label>Email:
                    <input type="email" name="email" required>
                </label><br>
                <label>パスワード:
                    <input type="password" name="new_password" required>
                </label><br>
                <button type="submit" name="register">登録</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("registerModal");
        const openBtn = document.getElementById("openModal");
        const closeBtn = document.getElementById("closeModal");

        openBtn.onclick = () => modal.style.display = "block";
        closeBtn.onclick = () => modal.style.display = "none";
        window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }
    </script>
</body>
</html>
