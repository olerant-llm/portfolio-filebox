<?php
session_start();
require_once __DIR__ . '/config.php';

// すでにログインしている場合はダッシュボードへリダイレクト
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF確認（ここでは簡易的に POST のみの受付）
    $password = $_POST['password'] ?? '';
    
    // パスワード照合
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        // セッション固定化攻撃対策
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        
        // 初回ログインなどのタイミングでデータベースの初期化を実施
        require_once __DIR__ . '/init_db.php';
        initDatabase();
        
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = 'パスワードが間違っています。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン - filebox</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-bg">
    <div class="login-wrapper">
        <div class="login-box shadow-box">
            <h1 class="logo-title">📦 filebox</h1>
            <p class="login-subtitle">管理者ログイン</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" name="password" id="password" class="form-control" required autofocus placeholder="パスワードを入力">
                </div>
                <button type="submit" class="btn btn-primary w-100">ログイン</button>
            </form>
        </div>
    </div>
</body>
</html>
