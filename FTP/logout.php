<?php
session_start();

// セッション変数をすべて解除する
$_SESSION = array();

// もしクッキーに基づくセッションを使用している場合、セッションクッキーも削除する
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 最終的に、セッションを破壊する
session_destroy();

header('Location: index.php');
exit;
