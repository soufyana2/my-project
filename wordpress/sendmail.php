<?php
ini_set('display_errors', 0); // فعّل مؤقتًا للتصحيح
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'db.php';
require_once 'functions.php';

// تحقق من وجود autoload
// Monolog MODIFIED: التحقق من وجود autoload مع تسجيل حرج
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    getLogger('setup')->critical('FATAL ERROR: vendor/autoload.php not found. Run "composer install".');
    // يمكنك عرض صفحة خطأ مخصصة هنا للمستخدم
    http_response_code(500);
    exit("A technical error occurred. please try again later.");
}
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// تحقق من وجود ملف keys.env
if (!file_exists(__DIR__ . '/keys.env')) {
    getLogger('setup')->critical('FATAL ERROR: keys.env file not found.', ['path' => __DIR__]);
    http_response_code(500);
    exit("A technical error occurred. please try again later.");
}

// تحميل ملف keys.env
try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'keys.env');
    $dotenv->load();
} catch (Exception $e) {
    getLogger('setup')->critical('Failed to load keys.env file.', ['error' => $e->getMessage()]);
    http_response_code(500);
    exit("A technical error occurred. please try again later.");
}

$ip = getClientIP();
$email = $_SESSION['user_email'] ?? $_POST['email'] ?? '';
$otp = $_SESSION['otp_code'] ?? '';
$username = $_SESSION['user_username'] ?? '';
$is_resend = isset($_GET['resent']) && $_GET['resent'] == 1;
$device_changed = isset($_GET['device_changed']) && $_GET['device_changed'] == 1;



// التحقق من وجود البريد الإلكتروني
if (empty($email)) {
    $error_message =  "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
    getLogger('auth')->warning('sendmail.php accessed with no email in session.', ['ip' => $ip]);
    header("Location: otp-page.php?error=");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Invalid email or password.";
    log_otp_attempt($pdo, $ip, $email, 'otp_resend', 'failed');
    getLogger('auth')->warning('sendmail.php accessed with an invalid email format.', ['ip' => $ip, 'email_attempt' => $email]);
    header("Location: otp-page.php?error=");
    exit();
}

$username = filter_var($username, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
if (empty($username)) {
    $username = 'Anonymouse';
}

if ($device_changed) {
    $error_message = "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
    log_otp_attempt($pdo, $ip, $email, 'otp_resend', 'failed');
    getLogger('security')->warning('OTP send aborted due to device/IP change.', [
        'ip' => $ip,
        'email' => $email
    ]);

    // Return JSON for AJAX
    echo json_encode([
        "status" => "redirect",
        "location" => "otp-page.php?error=" . urlencode($error_message)
    ]);
    exit();
}


$mail = new PHPMailer(true);

ob_start();
try {
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int)$_ENV['SMTP_PORT'];

    $mail->SMTPDebug = 0;
    $mail->Debugoutput = function ($str, $level) {
        getLogger('mail_debug')->debug("PHPMailer", ['level' => $level, 'message' => $str]);
    };

    $mail->setFrom($_ENV['SMTP_USERNAME'], 'رمز التحقق - Abdolwahab Accessories ');
    $mail->addAddress($email, $username);
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $mail->Subject = $is_resend ? 'إعادة إرسال رمز التحقق' : 'رمز التحقق الخاص بك';
  $mail->Body = "
<div style='font-family: Arial, sans-serif; direction: rtl; text-align: right; background-color: #f8f8f8; padding: 40px 0;'>
    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e5e5e5;'>

        <!-- Header -->
        <div style='background-color: #000000; padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-family: \"Playfair Display\", serif; letter-spacing: 1px; font-size: 24px;'>Abdolwahab</h1>
            <p style='color: #C8A95A; margin: 5px 0 0; font-size: 10px; text-transform: uppercase; letter-spacing: 2px;'>Parfums & Accessories</p>
        </div>

        <!-- Body -->
        <div style='padding: 40px 30px; color: #333333;'>
            
            <h2 style='font-size: 20px; color: #000; margin-bottom: 20px; text-align:center;'>رمز التحقق الخاص بك</h2>

            <p style='font-size: 15px; line-height: 1.8; color: #555;'>
                مرحبًا 
            </p>

            <p style='font-size: 16px; color: #333;'>
                رمز التحقق الخاص بك هو:
            </p>

            <div style='text-align: center; margin: 30px 0;'>
                <span style='
                    display: inline-block;
                    padding: 14px 30px;
                    font-size: 22px;
                    font-weight: bold;
                    background-color: #000000;
                    color: #C8A95A;
                    border-radius: 50px;
                    border: 1px solid #C8A95A;
                    letter-spacing: 4px;
                '>" . htmlspecialchars($otp, ENT_QUOTES, "UTF-8") . "</span>
            </div>

            <p style='font-size: 14px; color: #555; line-height: 1.8;'>
                هذا الرمز صالح لمدة <strong>5 دقائق</strong>.<br>
                الرجاء إدخاله في صفحة التحقق لإكمال عملية التسجيل.
            </p>

            <p style='font-size: 14px; color: #999;'>
                إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد الإلكتروني.
            </p>

        </div>

        <!-- Footer -->
        <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
            <p style='font-size: 12px; color: #999; margin: 0 0 10px;'>&copy; " . date('Y') . " Abdolwahab Accessories. جميع الحقوق محفوظة.</p>
            <div style='margin-top: 15px; font-size: 11px; color: #aaa;'>
                Dev & Design by 
                <a href='https://www.primestore.ma' style='color: #C8A95A; text-decoration: none; font-weight: bold;'>Primestore</a>
            </div>
        </div>

    </div>
</div>
";

    $mail->AltBody = "مرحبًا " .  "،\n\nرمز التحقق الخاص بك هو: " . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "\n\nهذا الرمز صالح لمدة 5 دقائق. الرجاء إدخاله في صفحة التحقق لإكمال التسجيل.\n\nإذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد.\n\nشكرًا،\nفريق التطبيق";

    if ($mail->send()) {
        $_SESSION['otp_resend_mode'] = true;
        $_SESSION['is_initial_otp'] = !$is_resend;
        increment_attempts_otp($pdo, $ip, $email, 'otp_resend');
        log_otp_attempt($pdo, $ip, $email, 'otp_resend', 'success');
        getLogger('mail')->info('OTP email sent successfully.', [
            'email' => $email,
            'ip' => $ip,
            'is_resend' => $is_resend
        ]);
        ob_get_clean();
        $redirect_url = $is_resend ? "otp-page.php?resent=1" : "otp-page.php";
        header("Location: $redirect_url");
        exit();
    } else {
        throw new Exception("فشل إرسال البريد: " . $mail->ErrorInfo);
    }
} catch (Exception $e) {
    $error_message =  "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
    getLogger('mail')->error('PHPMailer failed to send email.', [
        'email' => $email,
        'ip' => $ip,
        'error_info' => $mail->ErrorInfo, // خطأ PHPMailer المحدد
        'exception_message' => $e->getMessage()
    ]);
    log_otp_attempt($pdo, $ip, $email, 'otp_resend', 'failed');
    ob_get_clean();
    header("Location: otp-page.php?error=");
    exit();
}
?>