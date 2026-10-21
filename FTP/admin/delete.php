<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        $pdo = getDbConnection();
        // 該当ファイルの情報を取得
        $stmt = $pdo->prepare("SELECT stored_path FROM shared_files WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        if ($row) {
            // DBから削除
            $delStmt = $pdo->prepare("DELETE FROM shared_files WHERE id = :id");
            $delStmt->execute([':id' => $id]);
            
            // 物理ファイルを削除
            $stored_path = $row['stored_path'];
            if (file_exists($stored_path) && is_file($stored_path)) {
                unlink($stored_path);
            }
        }
    } catch (Exception $e) {
        // エラーログ等
    }
}

// 削除後ダッシュボードへリダイレクト
header('Location: dashboard.php');
exit;
