<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

$file_id = $_GET['id'] ?? '';

// セッション認証チェック
if (empty($_SESSION['auth_files'][$file_id])) {
    die("不正なアクセスです。認証が完了していません。");
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM shared_files WHERE file_id = :file_id");
$stmt->execute([':file_id' => $file_id]);
$file = $stmt->fetch();

if (!$file || !file_exists($file['stored_path'])) {
    die("ファイルが存在しません。");
}

// 物理ファイルの存在と読み取り権限を確認
$stored_path = $file['stored_path'];
if (!is_readable($stored_path)) {
    die("ファイルの読み取り権限がありません。");
}

// ダウンロード回数のカウントアップ
$updateStmt = $pdo->prepare("UPDATE shared_files SET download_count = download_count + 1 WHERE id = :id");
$updateStmt->execute([':id' => $file['id']]);

// ダウンロード完了後に認証状態をリセット（リンクを再利用した連続ダウンロードを防ぐ場合）
// unset($_SESSION['auth_files'][$file_id]); 

// ファイル出力処理
$original_name = $file['original_name'];
$filesize = filesize($stored_path);

// MS IE などへの対応を含めつつ、標準的なヘッダーを送信
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($original_name) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $filesize);

// 出力バッファをクリアしてメモリ拡張を防ぐ
if (ob_get_length()) {
    ob_clean();
}
flush();

// 実ファイルを読み込んで出力する
readfile($stored_path);
exit;
