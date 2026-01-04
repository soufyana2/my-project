<?php
// نبدأ تخزين المخرجات لضمان عدم خروج أي حرف قبل JSON
ob_start();

ini_set('display_errors', 0); // إخفاء الأخطاء عن المتصفح لضمان سلامة JSON
error_reporting(E_ALL);
session_start();

include("db.php");
require_once 'functions.php';
include("headers-policy.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use Dotenv\Dotenv;

// استدعاء الدوال الأساسية
redirectIfBlocked($pdo, getClientIP());
manage_csrf_token(); // ينشئ التوكن أو يتحقق منه

if (file_exists(__DIR__ . '/keys.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__, 'keys.env');
    $dotenv->load();
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ملف الإعدادات مفقود.']);
        exit;
    }
    http_response_code(500);
    exit("Technical Error.");
}

$error_message = '';
$is_blocked = false;
$lock_remaining_time = 0; 

// ---------------- معالجة طلب AJAX (المنطق الذي يعمل) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); // تنظيف المخرجات السابقة
    header('Content-Type: application/json; charset=utf-8');

    try {
        check_csrf(); // التحقق من التوكن القادم

        // 1. التحقق من Turnstile
        $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
        if (!validate_turnstile_response($turnstile_token)) {
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            handle_password_reset_attempts($pdo, $email, 'forgot', true);
            
            // إرسال new_csrf لتحديثه في المتصفح للمحاولة التالية
            echo json_encode([
                'status' => 'error', 
                'message' => "فشل التحقق الأمني (CAPTCHA). حاول مرة أخرى.",
                'new_csrf' => $_SESSION['csrf_token'] 
            ]);
            exit;
        }

        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        
        // 2. التحقق من التكرار (Rate Limit)
        $rate_limit_check = handle_password_reset_attempts($pdo, $email, 'forgot', false);
        
        if ($rate_limit_check['blocked']) {
            echo json_encode([
                'status' => 'blocked', 
                'message' => "تجاوزت الحد المسموح من المحاولات.",
                'remaining_seconds' => $rate_limit_check['remaining_seconds'],
                'new_csrf' => $_SESSION['csrf_token']
            ]);
            exit;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handle_password_reset_attempts($pdo, $email, 'forgot', true);
            echo json_encode([
                'status' => 'error', 
                'message' => "صيغة البريد الإلكتروني غير صحيحة.",
                'new_csrf' => $_SESSION['csrf_token']
            ]);
            exit;
        } else {
            // المحاولة صحيحة
            handle_password_reset_attempts($pdo, $email, 'forgot', true);
            $token = create_password_reset_token($pdo, $email);
            
            if ($token) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $_ENV['SMTP_HOST'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['SMTP_USERNAME'];
                    $mail->Password = $_ENV['SMTP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = (int)$_ENV['SMTP_PORT'];
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Abdolwahab Accessories');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'استعادة كلمة المرور - Abdolwahab Accessories';
                    
                    $reset_link = "http://localhost:8088/myproject/wordpress/reset_password.php?token=" . urlencode($token);
                    $year = date('Y');

                    // --- تصميم الإيميل الجديد (Abdolwahab/Vynix) ---
                    $email_template = "
                    <div style='font-family: Arial, sans-serif; direction: rtl; text-align: right; background-color: #f8f8f8; padding: 40px 0;'>
                        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e5e5e5;'>
                            
                            <!-- Header -->
                            <div style='background-color: #000000; padding: 30px; text-align: center;'>
                                <h1 style='color: #ffffff; margin: 0; font-family: \"Playfair Display\", serif; letter-spacing: 1px; font-size: 24px;'>Abdolwahab</h1>
                                <p style='color: #C8A95A; margin: 5px 0 0; font-size: 10px; text-transform: uppercase; letter-spacing: 2px;'>Parfums & Accessories</p>
                            </div>

                            <!-- Body -->
                            <div style='padding: 40px 30px; color: #333333;'>
                                <h2 style='font-size: 20px; color: #000; margin-bottom: 20px;'>مرحباً بك،</h2>
                                <p style='font-size: 15px; line-height: 1.8; color: #555;'>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في متجر عبدالوهاب. لإكمال العملية، يرجى الضغط على الزر أدناه:</p>
                                
                                <div style='text-align: center; margin: 35px 0;'>
                                    <a href='$reset_link' style='background-color: #000000; color: #C8A95A; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; display: inline-block; border: 1px solid #C8A95A;'>إعادة تعيين كلمة المرور</a>
                                </div>
                                
                                <p style='font-size: 13px; color: #777; margin-top: 30px;'>أو يمكنك نسخ الرابط التالي ولصقه في المتصفح:</p>
                                <p style='font-size: 12px; color: #000; word-break: break-all; background: #f4f4f4; padding: 10px; border-radius: 4px;'>$reset_link</p>
                            </div>

                            <!-- Footer -->
                            <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
                                <p style='font-size: 12px; color: #999; margin: 0 0 10px;'>&copy; $year Abdolwahab Accessories. جميع الحقوق محفوظة.</p>
                                <div style='margin-top: 15px; font-size: 11px; color: #aaa;'>
                <a href='https://www.primestore.ma' style='color: #C8A95A; text-decoration: none; font-weight: bold;'>Primestore</a>
                                </div>
                            </div>
                        </div>
                    </div>";

                    $mail->Body = $email_template;
                    $mail->AltBody = "الرابط: $reset_link";

                    $mail->send();
                    getLogger('auth')->info('تم إرسال بريد إعادة تعيين كلمة المرور.', ['email' => $email]);            
                } catch (Exception $e) {
                    getLogger('general')->error('فشل إرسال بريد إعادة تعيين كلمة المرور.', ['error' => $e->getMessage()]);
                }
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => "إذا كان البريد مسجلاً، سيتم إرسال التعليمات إليه.",
                'new_csrf' => $_SESSION['csrf_token']
            ]);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'خطأ غير متوقع.',
            'new_csrf' => $_SESSION['csrf_token']
        ]);
        exit;
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور - Abdolwahab Accessories</title>
    
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://challenges.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- CSS مطابق لصفحة Register تماماً -->
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #ffffff; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            padding-top: 60px;
        }
        
        .font-cairo { font-family: 'Cairo', sans-serif; }
        .font-logo { font-family: 'Playfair Display', serif; }

        .page-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center; 
            background-color: transparent; 
            z-index: 50;
            pointer-events: none;
        }
        .ltr-force { direction: ltr; display: inline-block; }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            direction: ltr; 
            pointer-events: auto;
        }
        .logo-img { height: 60px; width: auto; object-fit: contain; }
        .logo-text-group { display: flex; flex-direction: column; align-items: flex-start; color: #000; }
        .logo-main { font-size: 1.5rem; font-weight: 700; line-height: 1; letter-spacing: 0.05em; }
        .logo-sub { font-size: 0.65rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.15em; margin-top: 2px; color: #333; }

        @media (min-width: 768px) {
            .page-header { position: absolute; justify-content: flex-end; }
        }

        .auth-container {
            width: 100%;
            max-width: 440px; 
            padding: 2rem;
            position: relative;
            z-index: 100 !important;
        }

        /* حقول الإدخال */
        .custom-input {
            background-color: transparent;
            border: none;
            border-bottom: 2px solid #9ca3af; 
            border-radius: 0;
            padding: 1rem 0.25rem; 
            width: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #111827; 
            font-size: 1rem; 
        }
        .custom-input:focus { outline: none; box-shadow: none; border-bottom-color: #C8A95A; }
        .custom-input::placeholder { color: #6b7280; font-size: 0.95rem; transition: color 0.3s; font-weight: 500; }

        /* الأزرار */
        .btn-primary-pro {
            width: 100%;
            padding: 1rem 1.5rem;
            border-radius: 9999px;
            background-color: #000000;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.025em;
            position: relative;
            overflow: hidden; 
        }
        .btn-primary-pro::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%);
            transform: skewX(-25deg);
            transition: none; 
        }

        @media (hover: hover) {
            .btn-primary-pro:not(:disabled):hover { background-color: #000; }
            .btn-primary-pro:not(:disabled):hover::after { animation: shine 0.75s ease-in-out forwards; }
        }
        @keyframes shine { 100% { left: 150%; } }
        .btn-primary-pro:disabled { background-color: #9ca3af; cursor: not-allowed; opacity: 0.7; box-shadow: none; }

        .form-wrapper { transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; opacity: 1; transform: translateY(0); }
        .error-box { background-color: transparent; border: none; padding: 0.5rem 0; margin-bottom: 0.5rem; width: 100%; }
        
        /* Animation for messages */
        .slide-in { animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="page-header">
        <div class="logo-container">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1765686271/wmremove-transformed_2_1_roya1b.jpg" alt="شعار عبدالوهاب للعطور" class="logo-img">
            <div class="logo-text-group font-logo">
                <span class="logo-main">Abdolwahab</span>
                <span class="logo-sub">Accessories & Parfums</span>
            </div>
        </div>
    </header>

    <main class="auth-container">
        <div class="form-wrapper">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 font-cairo tracking-tight">استعادة كلمة المرور</h1>
            </div>
            
            <!-- منطقة عرض الرسائل -->
            <div id="message-container">
                <?php if ($is_blocked): ?>
                    <div class="p-4 mb-6 border rounded-md bg-gray-50 text-gray-700 text-center">
                        <p class="text-sm">
                            عفواً، تم حظرك مؤقتاً.
                            <span id="countdown-timer" class="font-bold text-red-600"></span>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <form id="forgot-password-form" class="space-y-6" method="POST" action="forgot_password.php">
                
                <!-- هذا الحقل سيتم تحديثه تلقائياً بالجافاسكربت بعد كل محاولة -->
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="space-y-5">
                    <div>
                        <input name="email" id="email" type="email" class="custom-input" placeholder="البريد الإلكتروني" required>
                    </div>
                </div>

                <!-- الكابتشا -->
                <div id="shared-turnstile-widget" class="cf-turnstile scale-90" data-sitekey="<?php echo htmlspecialchars($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']); ?>" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired" style="display: flex; justify-content: center; margin-top: 1rem;"></div>

                <button type="submit" id="submit-button" class="btn-primary-pro" disabled>
                    إرسال رابط الاستعادة
                </button>

                <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                    <a href="register.php" class="font-bold hover:underline transition-all" style="color:#C8A95A; font-size: 0.95rem;">العودة إلى تسجيل الدخول</a>
                </div>
            </form>
        </div>
    </main>

    <!-- JavaScript - نفس المنطق الذي كان يعمل تماماً -->
    <script>
        let remainingTime = <?php echo (int)$lock_remaining_time; ?>;
        let timerInterval = null;

        document.addEventListener('DOMContentLoaded', function () {
            const isBlockedInitial = <?php echo json_encode($is_blocked); ?>;
            
            if (isBlockedInitial && remainingTime > 0) {
                disableForm();
                startCountdown();
            }

            const form = document.getElementById('forgot-password-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleAjaxSubmit(form);
                });
            }
        });

        function handleAjaxSubmit(form) {
            const submitBtn = document.getElementById('submit-button');
            const originalBtnText = submitBtn.innerText;
            const messageContainer = document.getElementById('message-container');
            const csrfInput = document.getElementById('csrf_token');

            // 1. حالة التحميل
            submitBtn.disabled = true;
            submitBtn.innerText = 'جاري المعالجة...';
            messageContainer.innerHTML = ''; 

            const formData = new FormData(form);

            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // التأكد من أن الرد هو JSON صحيح
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Invalid JSON:", text);
                        throw new Error("حدث خطأ في الاتصال بالخادم.");
                    }
                });
            })
            .then(data => {
                // ✨ المنطق الذي يعمل: تحديث CSRF Token للمحاولة القادمة
                if (data.new_csrf && csrfInput) {
                    csrfInput.value = data.new_csrf;
                }

                if (data.status === 'success') {
                    renderMessage('success', data.message);
                    form.reset(); 
                    // نعيد وضع التوكن الجديد لأن reset() قد تمسحه
                    if (data.new_csrf) csrfInput.value = data.new_csrf;
                } 
                else if (data.status === 'blocked') {
                    remainingTime = parseInt(data.remaining_seconds);
                    renderMessage('blocked', data.message);
                    disableForm();
                    startCountdown();
                } 
                else {
                    // حالة الخطأ العادي
                    renderMessage('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                renderMessage('error', 'حدث خطأ غير متوقع، يرجى تحديث الصفحة والمحاولة مجدداً.');
            })
            .finally(() => {
                // ✨ أهم جزء: إعادة تعيين الكابتشا دائماً ليعمل في المحاولة الثانية
                if (window.turnstile) {
                    window.turnstile.reset();
                }

                // نعيد الزر لحالته الأصلية إذا لم يكن محظوراً
                if (remainingTime <= 0) {
                    submitBtn.innerText = originalBtnText;
                    // ملاحظة: يبقى الزر disabled حتى يقوم المستخدم بحل الكابتشا الجديدة وتفعيل onTurnstileSuccess
                }
            });
        }
function renderMessage(type, text) {
            const container = document.getElementById('message-container');
            let colorClass = '';
            
            // تحديد لون النص فقط (بدون خلفيات)
            if (type === 'success') colorClass = 'text-green-600';
            else if (type === 'error') colorClass = 'text-red-600';
            else if (type === 'blocked') colorClass = 'text-gray-700';

            // التعديلات:
            // 1. bg-transparent: خلفية شفافة
            // 2. text-xs: خط صغير
            // 3. flex ... gap-1: لضمان بقاء العداد بجانب النص في نفس السطر
            let html = `
                <div class="mb-4 bg-transparent text-center slide-in">
                    <div class="flex flex-wrap justify-center items-center gap-1 ${colorClass} text-xs font-bold">
                        <span>${text}</span>
                        ${type === 'blocked' ? '<span id="countdown-timer" class="text-red-600 font-extrabold ltr-force"></span>' : ''}
                    </div>
                </div>`;
            
            container.innerHTML = html;
        }
        function formatTime(totalSeconds) {
            if (totalSeconds < 0) totalSeconds = 0;
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function startCountdown() {
            const el = document.getElementById('countdown-timer');
            if(el) el.textContent = formatTime(remainingTime);

            if (timerInterval) clearInterval(timerInterval);

            timerInterval = setInterval(() => {
                remainingTime--;
                const elUpdated = document.getElementById('countdown-timer');
                if (elUpdated) elUpdated.textContent = formatTime(remainingTime);

                if (remainingTime <= 0) {
                    clearInterval(timerInterval);
                    window.location.reload(); 
                }
            }, 1000);
        }

        function disableForm() {
            const btn = document.getElementById('submit-button');
            const emailInput = document.getElementById('email');
            if(btn) btn.disabled = true;
            if(emailInput) emailInput.disabled = true;
        }

        // عند حل الكابتشا
        function onTurnstileSuccess(token) {
            if (remainingTime <= 0) {
                document.getElementById('submit-button').disabled = false;
            }
        }

        // عند انتهاء صلاحيتها
        function onTurnstileExpired() {
            document.getElementById('submit-button').disabled = true;
        }
    </script>
</body>
</html>