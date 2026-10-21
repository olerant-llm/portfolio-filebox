<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '権限がありません。']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '許可されていないメソッドです。']);
    exit;
}

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'ファイルのアップロードに失敗しました (エラーコード: ' . ($file['error'] ?? '不明') . ')']);
    exit;
}

if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['success' => false, 'error' => 'ファイルサイズが大きすぎます (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB以下にしてください)']);
    exit;
}

// パスワードの生成または取得
$password = !empty($_POST['password']) ? trim($_POST['password']) : substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// ファイルIDの生成
$file_id = bin2hex(random_bytes(16));
$original_name = basename($file['name']);
$stored_path = UPLOADS_DIR . '/' . $file_id . '.dat';

// 保存先のディレクトリがない場合は作成
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

if (move_uploaded_file($file['tmp_name'], $stored_path)) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO shared_files (file_id, original_name, stored_path, password_hash) 
            VALUES (:file_id, :original_name, :stored_path, :password_hash)
        ");
        $stmt->execute([
            ':file_id' => $file_id,
            ':original_name' => $original_name,
            ':stored_path' => $stored_path,
            ':password_hash' => $password_hash
        ]);
        
        $db_id = $pdo->lastInsertId();
        $created_at = date('Y-m-d H:i:s');
        $downloadUrl = SYSTEM_BASE_URL . '/public/download.php?id=' . urlencode($file_id);

        echo json_encode([
            'success' => true,
            'id' => $db_id,
            'original_name' => $original_name,
            'created_at' => $created_at,
            'file_id' => $file_id,
            'password' => $password, // 一度きりのパスワード表示
            'url' => $downloadUrl,
            'message' => 'ファイルが正常にアップロードされました。'
        ]);
    } catch (Exception $e) {
        if(file_exists($stored_path)) unlink($stored_path);
        echo json_encode(['success' => false, 'error' => 'データベースエラーが発生しました: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ファイルの保存に失敗しました。']);
}
