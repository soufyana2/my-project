<?php
ob_start();

session_set_cookie_params([
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();
include("db.php");
require_once 'functions.php';
include("headers-policy.php");
redirectIfBlocked($pdo, getClientIP());
manage_csrf_token();
$ip = getClientIP();
$login_input = $_SESSION['user_email'] ?? '';
$config = include('config.php');
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// تحقق من وجود ملف keys.env
if (!file_exists(__DIR__ . '/keys.env')) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => 'Technical error: keys missing']);
        exit;
    }
    getLogger('setup')->critical('FATAL ERROR: keys.env file not found.', ['path' => __DIR__]);
    http_response_code(500);
    exit("A technical error occurred.please try again later.");
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'keys.env');
    $dotenv->load();
} catch (Exception $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => 'Technical error: keys load failed']);
        exit;
    }
    getLogger('setup')->critical('Failed to load keys.env file.', ['error' => $e->getMessage()]);
    http_response_code(500);
    exit("A technical error occurred.please try again later.");
}

$session_timeout = 60 * 60;
$now = time();
if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $session_timeout) {
    getLogger('auth')->info('User session expired on OTP page.', [
        'user_email' => $login_input,
        'ip' => $ip
    ]);
    session_unset();
    session_destroy();
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'redirect', 'url' => 'register.php?timeout=1']);
        exit;
    }

    header("Location: register.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = $now;

$resend_remaining = 0;
$verify_remaining = 0;
$error_dynamic = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($login_input)) {
    $resend_check = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_resend', false);
    if ($resend_check['blocked']) {
        $resend_remaining = $resend_check['resend_remaining'];
        if (isset($resend_check['verify_remaining']) && $resend_check['verify_remaining'] > 0) {
            $verify_remaining = max($verify_remaining, $resend_check['verify_remaining']);
        }
    }

    $verify_check = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_verify', false);
    if ($verify_check['blocked']) {
        $verify_remaining = max($verify_remaining, $verify_check['verify_remaining']);
    }

    if ($resend_remaining > 0 || $verify_remaining > 0) {
        $messages = [];
        if ($resend_remaining > 0 && isset($resend_check['message'])) {
            $messages['resend'] = $resend_check['message'];
        }
        if ($verify_remaining > 0 && isset($verify_check['message'])) {
            $messages['verify'] = $verify_check['message'];
        }
        $unique_messages = array_unique($messages);
        $error_dynamic = implode(' ', $unique_messages);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['otp_code']) || !isset($_SESSION['user_email'])) {
        $errorMessage = urlencode("انتهت الجلسة، يرجى البدء من جديد.");
        header("Location: register.php?error=" . $errorMessage);
        exit();
    }
}

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $is_ajax = true;

    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    if (!validate_turnstile_response($turnstile_response)) {
        echo json_encode(['status' => 'error', 'message' => "عذرًا، تعذّر إتمام التحقق الأمني. يُرجى إعادة المحاولة."]);
        exit;
    } else {

    check_csrf(); 

    if (isset($_POST['resend_otp'])) {
        if (empty($login_input) || !isset($_SESSION['user_email']) || $login_input !== $_SESSION['user_email']) {
            $errorMessage = urlencode("انتهت الجلسة، يرجى البدء من جديد.");
            echo json_encode(['status' => 'redirect', 'url' => "register.php?error=" . $errorMessage]);
            exit;
        }

        $rate_limit_check = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_resend', false);
        if ($rate_limit_check['blocked'] || $rate_limit_check['resend_remaining'] > 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => $rate_limit_check['message'], 
                'resend_remaining' => $rate_limit_check['resend_remaining'],
                'verify_remaining' => 0,
                'new_csrf' => $_SESSION['csrf_token']
            ]);
            exit;
        } else {
            $attempt_check = handle_attempts_resend($pdo, $ip, $login_input);
            if ($attempt_check['blocked'] || $attempt_check['resend_remaining'] > 0) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => $attempt_check['message'], 
                    'resend_remaining' => $attempt_check['resend_remaining'],
                    'verify_remaining' => 0,
                    'new_csrf' => $_SESSION['csrf_token']
                ]);
                exit;
            } else {
                $_SESSION['user_email'] = $login_input;
                $_SESSION['otp_resend_mode'] = true;
                $_SESSION['resent_message'] = " ";
                echo json_encode(['status' => 'redirect', 'url' => "sendmail.php?resent=1"]);
                exit;
            }
        }
    }

    if (isset($_POST['sendotp'])) {
        $otpcode = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];

        if (!isset($_SESSION['otp_code'])) {
            echo json_encode(['status' => 'error', 'message' => 'Session Error', 'new_csrf' => $_SESSION['csrf_token']]);
            exit;
        } else {
            $attempt_check = handle_attempts_verify($pdo, $ip, $login_input, $otpcode);

            if ($attempt_check['blocked']) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => $attempt_check['message'], 
                    'resend_remaining' => 0,
                    'verify_remaining' => $attempt_check['verify_remaining'] ?? 0,
                    'new_csrf' => $_SESSION['csrf_token']
                ]);
                exit;
            } elseif ($attempt_check['message'] !== 'Otp code successfuly.') {
                $error_dynamic = $attempt_check['message'];
                $post_fail_check = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_verify', false);
                $v_rem = 0;
                if ($post_fail_check['blocked']) {
                    $error_dynamic = $post_fail_check['message'];
                    $v_rem = $post_fail_check['verify_remaining'] ?? 0;
                }
                echo json_encode([
                    'status' => 'error', 
                    'message' => $error_dynamic, 
                    'resend_remaining' => 0,
                    'verify_remaining' => $v_rem,
                    'new_csrf' => $_SESSION['csrf_token']
                ]);
                exit;
            } else {
                $email = $_SESSION["user_email"];
                $username = $_SESSION["user_username"];
                $password_hashed = $_SESSION["user_password_hashed"]; 
                unset($_SESSION['user_password_hashed']); 

                if (validate_email($email, $pdo) !== true || validate_username($username, $pdo) !== true) {
                    unset($_SESSION['user_email'], $_SESSION['user_username'], $_SESSION['user_password']);
                    echo json_encode(['status' => 'error', 'message' => "Invalid Data", 'new_csrf' => $_SESSION['csrf_token']]);
                    exit;
                }

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $password_hashed]);
                    session_regenerate_id(true);
                } catch (PDOException $e) {
                    echo json_encode(['status' => 'error', 'message' => "System Error", 'new_csrf' => $_SESSION['csrf_token']]);
                    exit;
                }

                unset($_SESSION['otp_hash'], $_SESSION['otp_salt'], $_SESSION['otp_code'], $_SESSION['otp_expire_time'], $_SESSION["user_email"], $_SESSION["user_username"], $_SESSION["user_password"], $_SESSION['otp_ip'], $_SESSION['otp_fingerprint'], $_SESSION['otp_resend_mode']);
                echo json_encode(['status' => 'redirect', 'url' => "register.php?success=1"]);
                exit;
            }
        }
    }
}}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من رمز OTP</title>
    
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://challenges.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<style>
    /* Global Styles */
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background-color: #ffffff;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        overflow-x: hidden;
        padding-top: 60px;
    }
    .font-cairo { font-family: 'Cairo', sans-serif; }
    .font-logo { font-family: 'Playfair Display', serif; }

    /* --- Header Styles (Logo Left Logic) --- */
    .page-header {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        /* في RTL: flex-end تعني اليسار */
         /* التعديل الأول: جعل الشعار في المنتصف افتراضياً (للموبايل) */
        justify-content: center; 
        
        background-color: transparent; 
        z-index: 50;
    }
    
    /* Logo Styling */
    .logo-container {
        display: flex;
        align-items: center;
        gap: 12px;
        direction: ltr; /* لضمان ظهور النص بجانب الشعار بشكل صحيح */
    }
    .logo-img {
        height: 60px; 
        width: auto;
        object-fit: contain;
    }
    .logo-text-group {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        color: #000;
    }
    .logo-main {
        font-size: 1.5rem; 
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0.05em;
    }
    .logo-sub {
        font-size: 0.65rem; 
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        margin-top: 2px;
        color: #333;
    }

    /* Container */
    .container {
        width: 100%;
        max-width: 28rem;
        background-color: white;
        padding: 1rem;
    }
    @media (min-width: 640px) {
        .container {
            padding: 1.5rem;
        }
    }

    /* --- OTP Inputs (LTR Flow for Numbers) --- */
    /* يجب أن يكون اتجاه الإدخال LTR حتى تظهر الأرقام 1-2-3-4-5-6 */
    .otp-input {
        width: 2.5rem;
        height: 2.5rem;
        min-width: 2.5rem;
        text-align: center;
        font-size: 1rem;
        font-weight: 600;
        border: 2px solid #d1d5db; 
        border-radius: 0.375rem;
        outline: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: transparent;
        color: #111827;
        direction: ltr; /* Force LTR inside input */
    }
    @media (min-width: 640px) {
        .otp-input {
            width: 3rem;
            height: 3rem;
            font-size: 1.125rem;
        }
    }
    .otp-input:focus {
        border-color: #C8A95A; 
        box-shadow: 0 0 0 1px #C8A95A;
    }

    /* --- Buttons --- */
    .submit-button {
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
        position: relative;
        overflow: hidden; 
        transition: background-color 0.2s;
    }
    
    .submit-button::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 50%;
        height: 100%;
        background: linear-gradient(
            to right,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.3) 50%,
            rgba(255, 255, 255, 0) 100%
        );
        transform: skewX(-25deg);
        transition: none; 
    }
    
/* --- Hover Logic (Desktop Only) --- */
    /* هذا الكود يضمن أن الهوفر يعمل فقط على الأجهزة التي بها ماوس */
    @media (hover: hover) and (min-width: 1024px) {
        /* هوفر زر التحقق */
        .submit-button:not(:disabled):hover {
            background-color: #333;
        }
        .submit-button:not(:disabled):hover::after {
            animation: shine 0.75s ease-in-out forwards;
        }

        /* هوفر رابط إعادة الإرسال */
        .resend-link:hover {
            color: #000000; 
            text-decoration: underline;
        }
    }

    /* تأكد من حذف أي كود آخر لـ :hover خارج هذا البلوك */
    @keyframes shine { 100% { left: 150%; } }

    .submit-button:disabled {
        background-color: #9ca3af;
        cursor: not-allowed;
        opacity: 0.7;
        box-shadow: none;
    }

    .resend-link {
        color: #C8A95A; 
        font-weight: 700;
        text-decoration: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        transition: color 0.2s ease-in-out;
    }
    .resend-link:disabled {
        color: #9ca3af;
        cursor: not-allowed;
        text-decoration: none;
    }
  /* التعديل الثاني: للشاشات الكبيرة فقط نعيده لليسار (RTL flex-end = Left) */
    @media (min-width: 768px) {
        .page-header {
            justify-content: flex-end; 
        }
    }
</style>
<body>

    <!-- Header: Logo on the LEFT (via justify-content: flex-end in RTL) -->
    <header class="page-header">
        <div class="logo-container">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1765686271/wmremove-transformed_2_1_roya1b.jpg" alt="شعار عبدالوهاب للعطور" class="logo-img">
            <div class="logo-text-group font-logo">
                <span class="logo-main">Abdolwahab</span>
                <span class="logo-sub">Accessories & Parfums</span>
            </div>
        </div>
    </header>

    <div class="w-full max-w-md container mt-10">
        <form id="otpForm" action="otp-page.php" method="POST">
            <div class="text-center p-6 pb-4">
                <h1 class="text-3xl font-bold text-gray-900 mb-2 font-cairo">تحقق من حسابك</h1>
                <p class="text-gray-600 text-sm">أدخل الرمز المكون من 6 أرقام الذي تم إرساله إلى بريدك الإلكتروني</p>
            </div>

            <div class="px-6 pb-4 space-y-4">
                <!-- OTP Inputs: Direction LTR for correct number flow -->
                <div class="flex justify-center gap-2" dir="ltr">
                    <input type="tel" inputmode="numeric" maxlength="1" name="otp1" class="otp-input">
                    <input type="tel" inputmode="numeric" maxlength="1" name="otp2" class="otp-input">
                    <input type="tel" inputmode="numeric" maxlength="1" name="otp3" class="otp-input">
                    <input type="tel" inputmode="numeric" maxlength="1" name="otp4" class="otp-input">
                    <input type="tel" inputmode="numeric" maxlength="1" name="otp5" class="otp-input">
                    <input type="tel" inputmode="numeric" maxlength="1" name="otp6" class="otp-input">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="screen_resolution" value="">
                    <input type="hidden" name="timezone" value="">
                    <input type="hidden" name="browser_plugins" value="">
                </div>
                
               <p class="text-sm text-gray-600 text-center min-h-[1.5rem]" id="dynamic-message">
                 <?php 
                if (isset($_GET['error']) && !empty($_GET['error'])) {
                    echo '<span class="text-red-600 font-medium">' . htmlspecialchars(urldecode($_GET['error'])) . '</span>';
                } elseif (isset($error_dynamic) && !empty($error_dynamic)) {
                    if (strpos($error_dynamic, '<span') === false) {
                        echo '<span class="text-red-600 font-medium">' . htmlspecialchars($error_dynamic) . '</span>';
                    } else {
                        echo $error_dynamic;
                    }
                } elseif (isset($_SESSION['resent_message'])) {
                    echo '<span class="text-green-600 font-bold">' . htmlspecialchars($_SESSION['resent_message']) . '</span>';
                    unset($_SESSION['resent_message']);
                }
                ?>
                </p>

                <!-- Resend Link: RTL Flow -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                       لم تستلم الرمز؟
                        <button 
                            type="button" 
                            name="resend_otp" 
                            id="resendBtn"
                            class="resend-link mr-1" 
                            <?php if ($resend_remaining > 0) echo 'disabled'; ?> >
                           إعادة إرسال الرمز
                        </button>
                    </p>
                </div>
            </div>
   
            <!-- ONE Turnstile Container -->
            <div id="turnstile-container" class="cf-turnstile scale-90" style="display: flex; justify-content: center; margin: 10px 0;"></div>
   
            <div class="p-6 pt-2">
                <button class="submit-button"
                        type="button"
                        name="sendotp"
                        id="verifyBtn"
                        <?php if ($verify_remaining > 0) echo 'disabled'; ?>
                        disabled
                        >
                    تحقق الآن
                </button>
            </div>
        </form> 
    </div>

    <script>
    let otpWidgetId = null; 
    let resend_sec = <?php echo json_encode($resend_remaining); ?>;
    let verify_sec = <?php echo json_encode($verify_remaining); ?>;
    const dynamicMessage = document.getElementById('dynamic-message');
    const resendBtn = document.getElementById('resendBtn');
    const verifyBtn = document.getElementById('verifyBtn');
    let combinedInterval;

    const siteKey = "<?php echo htmlspecialchars($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']); ?>";

    function formatTime(totalSeconds) {
        if (totalSeconds < 0) totalSeconds = 0;
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        const paddedMinutes = String(minutes).padStart(2, '0');
        const paddedSeconds = String(seconds).padStart(2, '0');
        return (totalSeconds >= 60) ? `${paddedMinutes}:${paddedSeconds}` : paddedSeconds;
    }

    const otpInputs = document.querySelectorAll('input[type="tel"]');
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });
    window.addEventListener('load', function() { if(otpInputs.length > 0) otpInputs[0].focus(); });

    function updateCombinedMessage() {
        resend_sec = Math.max(0, resend_sec - 1);
        verify_sec = Math.max(0, verify_sec - 1);

        let message = '';
        if (verify_sec > 0) {
            const formattedTime = formatTime(verify_sec);
            message = `التحقق محظور. يرجى الانتظار <span class="text-red-600 font-bold">${formattedTime}</span>.`;
        } else if (resend_sec > 0) {
            const formattedTime = formatTime(resend_sec);
            message = `إعادة الإرسال محظورة. يرجى الانتظار <span class="text-red-600 font-bold">${formattedTime}</span>.`;
        }
        
        if (dynamicMessage) {
            dynamicMessage.innerHTML = message;
        }

        if (verify_sec > 0) {
            if (verifyBtn) verifyBtn.disabled = true;
            if (resendBtn) resendBtn.disabled = true;
        } else if (resend_sec > 0) {
            if (verifyBtn) verifyBtn.disabled = false;
            if (resendBtn) resendBtn.disabled = true;
        }

        if (resend_sec <= 0 && verify_sec <= 0) {
            clearInterval(combinedInterval);
            if (dynamicMessage && dynamicMessage.innerHTML.includes('محظورة')) {
                dynamicMessage.innerHTML = '<span class="text-green-600 font-bold">يمكنك المحاولة الآن</span>';
            }
            
            // تحديث الكابتشا عند انتهاء العداد
            if (window.turnstile && otpWidgetId) {
                turnstile.reset(otpWidgetId);
            }
        }
    }

    if (resend_sec > 0 || verify_sec > 0) {
        updateCombinedMessage();
        combinedInterval = setInterval(updateCombinedMessage, 1000);
    }

    // تهيئة الكابتشا (واحدة فقط)
    window.onload = function() {
        if (typeof turnstile !== 'undefined') {
            otpWidgetId = turnstile.render('#turnstile-container', {
                sitekey: siteKey,
                callback: function(token) {
                    onTurnstileSuccess(token);
                },
                'expired-callback': function() {
                    onTurnstileExpired();
                }
            });
        }
    };

    function onTurnstileSuccess(token) {
        const submitButtons = document.querySelectorAll('#verifyBtn, #resendBtn');
        submitButtons.forEach(button => {
            if(button.id === 'verifyBtn' && verify_sec > 0) return;
            if(button.id === 'resendBtn' && resend_sec > 0) return;
            button.disabled = false;
        });
    }

    function onTurnstileExpired() {
         const submitButtons = document.querySelectorAll('#verifyBtn, #resendBtn');
         submitButtons.forEach(button => {
            button.disabled = true;
        });
    }

    // AJAX Handling
    async function handleAjaxSubmit(actionType) {
        const form = document.getElementById('otpForm');
        const formData = new FormData(form);
        
        if (actionType === 'verify') {
            formData.append('sendotp', '1');
        } else if (actionType === 'resend') {
            formData.append('resend_otp', '1');
        }

        if(verifyBtn) verifyBtn.disabled = true;
        if(resendBtn) resendBtn.disabled = true;
        if(dynamicMessage) dynamicMessage.innerHTML = '<span class="text-gray-500 font-medium">جاري المعالجة...</span>';

        try {
            const response = await fetch('otp-page.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.status === 'redirect') {
                window.location.href = data.url;
            } else if (data.status === 'error') {
                
                if (data.new_csrf) {
                    const csrfInput = document.querySelector('input[name="csrf_token"]');
                    if (csrfInput) csrfInput.value = data.new_csrf;
                }

                if (data.message) {
                    if (data.message.includes('<span')) {
                         dynamicMessage.innerHTML = data.message;
                    } else {
                         dynamicMessage.innerHTML = '<span class="text-red-600 font-medium">' + data.message + '</span>';
                    }
                }

                if (typeof data.resend_remaining !== 'undefined') resend_sec = data.resend_remaining;
                if (typeof data.verify_remaining !== 'undefined') verify_sec = data.verify_remaining;

                if (resend_sec > 0 || verify_sec > 0) {
                    clearInterval(combinedInterval);
                    updateCombinedMessage(); 
                    combinedInterval = setInterval(updateCombinedMessage, 1000);
                } else {
                    if (window.turnstile && otpWidgetId) turnstile.reset(otpWidgetId);
                }
            } 
        } catch (error) {
            console.error('Error:', error);
            if(dynamicMessage) dynamicMessage.innerHTML = '<span class="text-red-600">حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.</span>';
             if (window.turnstile && otpWidgetId) turnstile.reset(otpWidgetId);
        }
    }

    verifyBtn.addEventListener('click', function(e) {
        e.preventDefault();
        handleAjaxSubmit('verify');
    });

    resendBtn.addEventListener('click', function(e) {
        e.preventDefault();
        handleAjaxSubmit('resend');
    });

    </script>
</body>
</html>
