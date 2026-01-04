<?php
/**
 * Cron Job Cleaning Script
 * Safe, Professional, and Customized.
 */

// 1. الأمان: منع التشغيل من المتصفح (لن يظهر في متجرك أبداً)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Forbidden: CLI access only.');
}

// 2. تضمين الملفات المطلوبة
require_once 'vendor/autoload.php';
require_once 'logger_setup.php'; 
require_once 'db.php'; // تم إضافته كما طلبت (تأكد أن المتغير داخله اسمه $pdo)

// إعدادات المدة الزمنية (بالأيام)
$logs_retention_days = 25;      // الاحتفاظ بالسجلات لمدة 25 يوم
$attempts_retention_days = 2;   // الاحتفاظ بالمحاولات لمدة يومين فقط (للحفاظ على سرعة الموقع)
$general_cleanup_days = 15;     // تنظيفات عامة أخرى (مثل التوكنات المنتهية)

$cronLogger = getLogger('cron');
$cronLogger->info('--- Starting Cleanup Process ---');
$startTime = microtime(true);

try {
    if (!isset($pdo)) {
        throw new Exception("Database connection (\$pdo) not found in db.php");
    }

    // حساب التواريخ بالثواني (Unix Timestamp)
    $time_for_logs = time() - ($logs_retention_days * 24 * 60 * 60);
    $time_for_attempts = time() - ($attempts_retention_days * 24 * 60 * 60);
    // للتاريخ بصيغة MySQL (Y-m-d H:i:s)
    $date_for_general = date('Y-m-d H:i:s', time() - ($general_cleanup_days * 24 * 60 * 60));

    // =============================================
    // 1. تنظيف السجلات (Logs) - (25 يوم)
    // =============================================
    
    // تنظيف سجلات OTP
    $stmt = $pdo->prepare("DELETE FROM otp_logs WHERE created_at < ?");
    $stmt->execute([$time_for_logs]);
    $deletedOtpLogs = $stmt->rowCount();

    // تنظيف سجلات التسجيل
    $stmt = $pdo->prepare("DELETE FROM registration_logs WHERE created_at < ?");
    $stmt->execute([$time_for_logs]);
    $deletedRegLogs = $stmt->rowCount();

    // (NEW) تنظيف سجلات الدخول login_logs
    // أفترض أن الجدول يحتوي حقل created_at أو login_time
    // إذا كان الحقل timestamp (رقم) استخدم $time_for_logs
    // إذا كان الحقل datetime (تاريخ) استخدم date(...)
    // سأستخدم الصيغة الرقمية المتوافقة مع باقي جداولك:
    $stmt = $pdo->prepare("DELETE FROM login_logs WHERE created_at < ?"); 
    $stmt->execute([$time_for_logs]);
    $deletedLoginLogs = $stmt->rowCount();

    if ($deletedOtpLogs > 0 || $deletedRegLogs > 0 || $deletedLoginLogs > 0) {
        $cronLogger->info("Cleaned Logs (Older than $logs_retention_days days).", [
            'otp_logs' => $deletedOtpLogs,
            'reg_logs' => $deletedRegLogs,
            'login_logs' => $deletedLoginLogs
        ]);
    }

    // =============================================
    // 2. تنظيف المحاولات (Attempts) - (يومين لسرعة الموقع)
    // =============================================
    
    // login_attempts
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE updated_at < ?");
    $stmt->execute([$time_for_attempts]);
    
    // registration_attempts
    $stmt = $pdo->prepare("DELETE FROM registration_attempts WHERE updated_at < ?");
    $stmt->execute([$time_for_attempts]);

    // otp_attemptsssss
    $stmt = $pdo->prepare("DELETE FROM otp_attemptsssss WHERE updated_at < ?");
    $stmt->execute([$time_for_attempts]);

    // password_reset_attempts
    $stmt = $pdo->prepare("DELETE FROM password_reset_attempts WHERE last_attempt < ?");
    $stmt->execute([$time_for_attempts]);

    // ip_attemptss (عدادات الحظر اليومي)
    $stmt = $pdo->prepare("DELETE FROM ip_attemptss WHERE last_attempt < ?");
    $stmt->execute([$time_for_attempts]);

    $cronLogger->info("Cleaned Rate Limit Tables (Older than $attempts_retention_days days).");


    // =============================================
    // 3. تنظيف البيانات المنتهية الصلاحية (Expired Data)
    // =============================================

    // Remember Tokens (المنتهية فعلياً)
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    $stmt->execute();

    // Password Resets Tokens (المنتهية فعلياً)
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
    $stmt->execute();

    // Blocked IPs (التي انتهى وقت حظرها)
    $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE expiry < NOW()");
    $stmt->execute();


    // =============================================
    // النهاية
    // =============================================
    $duration = round(microtime(true) - $startTime, 4);
    $cronLogger->info("Cleanup Job Finished.", ['duration' => $duration]);
    
    // رسالة لمدير السيرفر (تصل للإيميل)
    echo "Success: Database cleanup completed in $duration seconds.";

} catch (Exception $e) {
    // تسجيل الخطأ
    $cronLogger->critical("Cleanup Failed.", ['error' => $e->getMessage()]);
    // إرسال الخطأ للإيميل
    echo "Error: " . $e->getMessage();
}