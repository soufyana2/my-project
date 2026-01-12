<?php
// نبدأ تخزين المخرجات لضمان عدم خروج أي حرف قبل JSON
ob_start();

ini_set('display_errors', 0); 
error_reporting(E_ALL);
session_start();

include("db.php");
require_once 'functions.php';
include("headers-policy.php");
require 'vendor/autoload.php';

use Dotenv\Dotenv;

// استدعاء الدوال الأساسية
redirectIfBlocked($pdo, getClientIP());
manage_csrf_token(); 

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
$token_is_valid = false;
$show_form = false;
$email = null;
$reset_data = null;

// 1. التحقق من التوكن عند تحميل الصفحة (GET)
$token_from_url = $_GET['token'] ?? '';

if (!empty($token_from_url)) {
    $reset_data = validate_password_reset_token($pdo, $token_from_url);

    if ($reset_data) {
        $token_is_valid = true;
        $email = $reset_data['email'];

        // التحقق من الحظر (نوع الإجراء: reset)
        $rate_limit_check = handle_password_reset_attempts($pdo, $email, 'reset', false);
        
        if ($rate_limit_check['blocked']) {
            $error_message = $rate_limit_check['message'];
            $lock_remaining_time = $rate_limit_check['remaining_seconds'];
            $is_blocked = true;
            $show_form = false;
        } else {
            $show_form = true;
        }
    } else {
        $error_message = "الرابط غير صالح أو منتهي الصلاحية.";
    }
} else {
    $error_message = "الرابط غير مكتمل.";
}

// ---------------- معالجة طلب AJAX (POST) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); 
    header('Content-Type: application/json; charset=utf-8');

    try {
        // إذا كان التوكن غير صالح أساساً
        if (!$token_is_valid) {
            echo json_encode(['status' => 'error', 'message' => $error_message, 'new_csrf' => $_SESSION['csrf_token']]);
            exit;
        }

        check_csrf(); // التحقق من CSRF

        // 1. التحقق من Turnstile
        $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
        if (!validate_turnstile_response($turnstile_token)) {
            // تسجيل محاولة فاشلة
            $lock_result = handle_password_reset_attempts($pdo, $email, 'reset', true);
            
            $response = [
                'status' => 'error', 
                'message' => "فشل التحقق الأمني (CAPTCHA).",
                'new_csrf' => $_SESSION['csrf_token'] 
            ];
            
            if ($lock_result['blocked']) {
                $response['status'] = 'blocked';
                $response['message'] = $lock_result['message'];
                $response['remaining_seconds'] = $lock_result['remaining_seconds'];
            }
            
            echo json_encode($response);
            exit;
        }
        
        // 2. التحقق من التكرار (Rate Limit) قبل المعالجة
        $rate_limit_check = handle_password_reset_attempts($pdo, $email, 'reset', false);
        if ($rate_limit_check['blocked']) {
            echo json_encode([
                'status' => 'blocked', 
                'message' => $rate_limit_check['message'],
                'remaining_seconds' => $rate_limit_check['remaining_seconds'],
                'new_csrf' => $_SESSION['csrf_token']
            ]);
            exit;
        }

        // 3. التحقق من كلمات المرور
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $password_validation = validate_password($new_password);

        if ($password_validation !== true) {
            // كلمة مرور ضعيفة
            $lock_result = handle_password_reset_attempts($pdo, $email, 'reset', true);
            $response = ['status' => 'error', 'message' => $password_validation, 'new_csrf' => $_SESSION['csrf_token']];
            
            if ($lock_result['blocked']) { 
                $response['status'] = 'blocked';
                $response['message'] = $lock_result['message'];
                $response['remaining_seconds'] = $lock_result['remaining_seconds'];
            }
            echo json_encode($response);
            exit;
        } elseif ($new_password !== $confirm_password) {
            // عدم تطابق
            $lock_result = handle_password_reset_attempts($pdo, $email, 'reset', true);
            $response = ['status' => 'error', 'message' => "كلمتا المرور غير متطابقتين.", 'new_csrf' => $_SESSION['csrf_token']];

            if ($lock_result['blocked']) {
                $response['status'] = 'blocked';
                $response['message'] = $lock_result['message'];
                $response['remaining_seconds'] = $lock_result['remaining_seconds'];
            }
            echo json_encode($response);
            exit;
        } else {
            // 4. التحديث الفعلي
            if (reset_user_password($pdo, $email, $new_password, $reset_data['token_hash'])) {
                rate_limit_reset_password($pdo, $email); // تصفير العداد
                echo json_encode([
                    'status' => 'success', 
                    'message' => "تم تغيير كلمة المرور بنجاح. جاري تحويلك...",
                    'new_csrf' => $_SESSION['csrf_token']
                ]);
                exit;
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => "حدث خطأ أثناء التحديث. حاول مرة أخرى.",
                    'new_csrf' => $_SESSION['csrf_token']
                ]);
                exit;
            }
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
<link rel="icon" type="image/svg+xml" href="public/images/favicon.svg">
  <title>تعيين كلمة المرور - Abdolwahab Accessories</title>
    
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://challenges.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- CSS (نفس التصميم المرسل) -->
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
        .password-field { position: relative; }
        .password-input { padding-inline-end: 2.75rem; }
        .password-toggle {
            position: absolute;
            top: 50%;
            inset-inline-end: 0.5rem;
            transform: translateY(-50%);
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.9);
            color: #6b7280;
            transition: all 0.2s ease;
        }
        .password-toggle:hover {
            background: #f3f4f6;
            color: #111827;
            border-color: #e5e7eb;
        }
        .password-toggle:focus-visible {
            outline: 2px solid #C8A95A;
            outline-offset: 2px;
        }
        .password-toggle svg { width: 18px; height: 18px; }
        .password-toggle .icon-eye-off { display: none; }
        .password-toggle[data-visible="true"] .icon-eye { display: none; }
        .password-toggle[data-visible="true"] .icon-eye-off { display: block; }
        .password-toggle[data-visible="true"] { color: #C8A95A; }

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

        @media (hover: hover) and (min-width: 1024px) {
            .btn-primary-pro:not(:disabled):hover { background-color: #000; }
            .btn-primary-pro:not(:disabled):hover::after { animation: shine 0.75s ease-in-out forwards; }
        }
        @keyframes shine { 100% { left: 150%; } }
        .btn-primary-pro:disabled { background-color: #9ca3af; cursor: not-allowed; opacity: 0.7; box-shadow: none; }

        .form-wrapper { transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; opacity: 1; transform: translateY(0); }
        
        /* Animation for messages */
        .slide-in { animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="page-header">
        <div class="logo-container">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png" alt="شعار عبدالوهاب للعطور" class="logo-img">
            <div class="logo-text-group font-logo">
                <span class="logo-main">Abdolwahab</span>
                <span class="logo-sub">Accessories & Parfums</span>
            </div>
        </div>
    </header>

    <main class="auth-container">
        <div class="form-wrapper">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 font-cairo tracking-tight">كلمة المرور الجديدة</h1>
            </div>
            
            <!-- منطقة عرض الرسائل -->
            <div id="message-container">
                <?php if (!empty($error_message) && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
                    <div class="mb-4 bg-transparent text-center slide-in">
                        <div class="flex flex-wrap justify-center items-center gap-1 <?php echo $is_blocked ? 'text-gray-700' : 'text-red-600'; ?> text-xs font-bold">
                            <span><?php echo htmlspecialchars($error_message); ?></span>
                            <?php if ($is_blocked): ?>
                                <span id="countdown-timer" class="text-red-600 font-extrabold ltr-force"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($show_form): ?>
            <form id="reset-password-form" class="space-y-6" method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token_from_url); ?>">
                
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="space-y-5">
                    <div class="password-field">
                        <input name="new_password" id="new_password" type="password" class="custom-input password-input" placeholder="كلمة المرور الجديدة" required>
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" data-visible="false">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 8 10 8a18.26 18.26 0 0 1-2.62 3.93"></path>
                                <path d="M6.1 6.1C3.53 8.22 2 12 2 12s3.5 6 10 6a10.94 10.94 0 0 0 5.76-1.62"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="password-field">
                        <input name="confirm_password" id="confirm_password" type="password" class="custom-input password-input" placeholder="تأكيد كلمة المرور" required>
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" data-visible="false">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 8 10 8a18.26 18.26 0 0 1-2.62 3.93"></path>
                                <path d="M6.1 6.1C3.53 8.22 2 12 2 12s3.5 6 10 6a10.94 10.94 0 0 0 5.76-1.62"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- الكابتشا -->
                <div id="shared-turnstile-widget" class="cf-turnstile scale-90" data-sitekey="<?php echo htmlspecialchars($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']); ?>" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired" style="display: flex; justify-content: center; margin-top: 1rem;"></div>

                <button type="submit" id="submit-button" class="btn-primary-pro" disabled>
                    حفظ كلمة المرور
                </button>
            </form>
            <?php elseif (empty($error_message)): ?>
                <div class="text-center mt-4">
                     <p class="text-sm text-gray-600">جاري التحقق من الرابط...</p>
                </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <a href="register.php" class="font-bold lg:hover:underline transition-all" style="color:#C8A95A; font-size: 0.95rem;">العودة إلى تسجيل الدخول</a>
            </div>
        </div>
    </main>

    <!-- JavaScript - منطق التعامل مع AJAX والكابتشا -->
    <script>
        let remainingTime = <?php echo (int)$lock_remaining_time; ?>;
        let timerInterval = null;
        let isBlocked = <?php echo json_encode($is_blocked); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            
            // تشغيل العداد إذا كان هناك حظر عند تحميل الصفحة
            if (isBlocked && remainingTime > 0) {
                disableForm();
                startCountdown();
            }

            const form = document.getElementById('reset-password-form');
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
            submitBtn.innerText = 'جاري الحفظ...';
            messageContainer.innerHTML = ''; 

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
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
                // تحديث CSRF Token للمحاولة القادمة
                if (data.new_csrf && csrfInput) {
                    csrfInput.value = data.new_csrf;
                }

                if (data.status === 'success') {
                    renderMessage('success', data.message);
                    form.style.display = 'none'; // إخفاء الفورم عند النجاح
                    
                    // تحويل المستخدم بعد ثانيتين
                    setTimeout(() => {
                        window.location.href = 'register.php';
                    }, 2000);
                } 
                else if (data.status === 'blocked') {
                    remainingTime = parseInt(data.remaining_seconds);
                    renderMessage('blocked', data.message);
                    disableForm();
                    startCountdown();
                } 
                else {
                    // خطأ عادي (كابتشا، كلمة مرور ضعيفة، إلخ)
                    renderMessage('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                renderMessage('error', 'حدث خطأ غير متوقع، يرجى تحديث الصفحة والمحاولة مجدداً.');
            })
            .finally(() => {
                // إعادة تعيين الكابتشا دائماً
                if (window.turnstile) {
                    window.turnstile.reset();
                }

                // إعادة الزر لحالته (معطلاً بانتظار حل الكابتشا مرة أخرى)
                if (remainingTime <= 0) {
                    submitBtn.innerText = originalBtnText;
                    // ملاحظة: الزر سيبقى disabled حتى يتم حل الكابتشا الجديدة
                }
            });
        }

        function renderMessage(type, text) {
            const container = document.getElementById('message-container');
            let colorClass = '';
            
            if (type === 'success') colorClass = 'text-green-600';
            else if (type === 'error') colorClass = 'text-red-600';
            else if (type === 'blocked') colorClass = 'text-gray-700';

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
            const pass1 = document.getElementById('new_password');
            const pass2 = document.getElementById('confirm_password');
            
            if(btn) btn.disabled = true;
            if(pass1) pass1.disabled = true;
            if(pass2) pass2.disabled = true;
        }

        // عند حل الكابتشا بنجاح
        function onTurnstileSuccess(token) {
            // فقط تفعيل الزر إذا لم يكن هناك حظر زمني
            if (remainingTime <= 0) {
                document.getElementById('submit-button').disabled = false;
            }
        }

        // عند انتهاء صلاحية الكابتشا
        function onTurnstileExpired() {
            document.getElementById('submit-button').disabled = true;
        }

        document.querySelectorAll('.password-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const field = btn.closest('.password-field');
                const input = field ? field.querySelector('input') : null;
                if (!input) return;
                const willShow = input.type === 'password';
                input.type = willShow ? 'text' : 'password';
                btn.dataset.visible = String(willShow);
                btn.setAttribute('aria-pressed', String(willShow));
                btn.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
                input.focus();
            });
        });
    </script>
</body>
</html>
