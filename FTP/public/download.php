<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

$file_id = $_GET['id'] ?? '';
if (!$file_id) {
    die("不正なアクセスです。");
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM shared_files WHERE file_id = :file_id");
$stmt->execute([':file_id' => $file_id]);
$file = $stmt->fetch();

if (!$file) {
    die("ファイルが見つかりません。削除されたか、URLが間違っています。");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (password_verify($password, $file['password_hash'])) {
        // 認証成功
        $_SESSION['auth_files'][$file_id] = true;
        header("Location: getfile.php?id=" . urlencode($file_id));
        exit;
    } else {
        $error = "パスワードが間違っています。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ファイルのダウンロード - filebox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-bg">
    <div class="login-wrapper">
        <div class="login-box shadow-box text-center">
            <h1 class="logo-title">📦 ダウンロード</h1>
            <p class="mb-4"><strong><?php echo htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8'); ?></strong></p>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group text-left">
                    <label for="password">ダウンロード用パスワード</label>
                    <input type="password" name="password" id="password" class="form-control" required autofocus placeholder="パスワードを入力">
                </div>
                <button type="submit" class="btn btn-primary w-100">ダウンロード開始</button>
            </form>
        </div>
    </div>
</body>
</html>
