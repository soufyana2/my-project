<?php
ob_start();
require_once 'logger_setup.php';

// إعدادات الكوكيز والجلسة
session_set_cookie_params([
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
include("db.php");
require_once 'functions.php';
require_once 'headers-policy.php';

// ❌ قمنا بحذف الشرط القديم الذي كان يرفض الطلب ويعيدك للرئيسية
// if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['logout'])) { ... }

// ✅ بدلاً من ذلك، نتحقق فقط من وجود جلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ** ملاحظة أمان **
// إذا أردت تفعيل CSRF مع GET، يجب إرسال التوكن في الرابط: logout.php?token=xyz
// للتسهيل عليك الآن، سنقوم بتسجيل الخروج مباشرة.

// 1. التقاط المعلومات للوج (Log)
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'N/A';
$ip = getClientIP();
$selector = null;

if (isset($_COOKIE['remember_token'])) {
    $cookie_data = base64_decode($_COOKIE['remember_token'], true);
    if ($cookie_data && substr_count($cookie_data, ':') === 1) {
        list($selector, $validator) = explode(':', $cookie_data, 2);
    }
}

// 2. التنظيف
if ($user_id) {
    clear_remember_me($pdo, $user_id, $selector);
}

setcookie('remember_token', '', time() - 3600, '/', '', true, true);
session_unset();
session_destroy();

// 3. تسجيل الحدث
if ($user_id) {
    $authLogger = getLogger('auth');
    $authLogger->info('User logged-out successfully.', [
        'user_id' => $user_id,
        'username' => $username,
        'ip' => $ip
    ]);
}

// ✅ التوجيه النهائي لصفحة التسجيل
ob_end_clean();
header("Location: register.php?logged_out=1");
exit;
?>