<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$pdo = getDbConnection();
$files = [];
try {
    $stmt = $pdo->query("SELECT * FROM shared_files ORDER BY created_at DESC");
    if ($stmt) {
        $files = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // テーブルがないなどのエラー
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - filebox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <header class="navbar">
        <div class="container d-flex justify-between align-items-center">
            <h1 class="logo-title m-0">📦 filebox Admin</h1>
            <a href="../logout.php" class="btn btn-outline btn-sm">ログアウト</a>
        </div>
    </header>
    
    <div class="container mt-4">
        <div class="shadow-box mb-4 bg-white p-4">
            <h2>ファイルのアップロード</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file">ファイルを選択 (最大 100MB)</label>
                    <input type="file" name="file" id="file" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">ダウンロード用パスワード (空欄の場合は 8文字で自動生成されます)</label>
                    <input type="text" name="password" id="password" class="form-control" placeholder="任意のパスワードを入力">
                </div>
                <button type="submit" class="btn btn-primary" id="uploadBtn">アップロード</button>
            </form>
            <div id="uploadResult" class="mt-3" style="display: none;"></div>
        </div>

        <div class="shadow-box bg-white p-4">
            <h2>アップロード済みファイル</h2>
            <div class="table-responsive">
                <table class="table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ファイル名</th>
                            <th>ダウンロードURL</th>
                            <th>DL数</th>
                            <th>アップロード日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)): ?>
                        <tr><td colspan="5" class="text-center text-muted">ファイルがありません。</td></tr>
                        <?php else: ?>
                            <?php foreach ($files as $f): 
                                // 固定のベースURLを使用してリンクを生成
                                $downloadUrl = SYSTEM_BASE_URL . '/public/download.php?id=' . urlencode($f['file_id']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($f['original_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" value="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>" readonly class="form-control url-input" style="min-width:200px;">
                                        <button type="button" class="btn btn-sm btn-outline copy-btn" data-url="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>">コピー</button>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo (int)$f['download_count']; ?></td>
                                <td><?php echo htmlspecialchars($f['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <form action="delete.php" method="post" onsubmit="return confirm('このファイルを本当に削除しますか？');" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">削除</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js?v=2"></script>
</body>
</html>
