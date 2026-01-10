<?php
ob_start();
session_start();



ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_set_cookie_params([
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'lax'
]);

include("db.php");
require_once 'functions.php';
 include("headers-policy.php");

require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// التحقق من ملف المفاتيح
if (!file_exists(__DIR__ . '/keys.env')) {
    if(is_ajax_request()) { echo json_encode(['status'=>'error', 'message'=>'Technical error: keys file missing']); exit; }
    http_response_code(500); exit("A technical error occurred.");
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'keys.env');
    $dotenv->load();
} catch (Exception $e) {
    if(is_ajax_request()) { echo json_encode(['status'=>'error', 'message'=>'Technical error: keys load failed']); exit; }
    http_response_code(500); exit("A technical error occurred.");
}

// الفحوصات الأمنية الأولية
check_remember_me($pdo);
redirectIfBlocked($pdo, getClientIP());
manage_csrf_token(); 

// التحقق من انتهاء الجلسة
$session_timeout = 60 * 60; 
$now = time();
if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $session_timeout) {
    session_unset();
    session_destroy();
    if(is_ajax_request()) {
         echo json_encode(['status'=>'error', 'message'=>'Session expired', 'redirect'=>'register.php?timeout=1']);
         exit;
    }
    header("Location: register.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = $now;

// =========================================================
//  معالج الطلبات (AJAX Handler)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax_request()) {
    header('Content-Type: application/json');
    
    // 1. فحص CSRF
    check_csrf(); 
    $new_csrf_token = $_SESSION['csrf_token']; 

    // 2. فحص الكابتشا
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    if (!validate_turnstile_response($turnstile_response)) {
        echo json_encode([
            'status' => 'error', 
            'message' => '', 
            'csrf_token' => $new_csrf_token,
            'reset_turnstile' => true
        ]);
        exit;
    }

    // --- معالجة التسجيل (Sign Up) ---
    if (isset($_POST['sign-up'])) {
        $ip = getClientIP();
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $signup_check = check_if_blocked_signup($pdo, $ip, $email);
            if ($signup_check['blocked']) {
                $remaining = $signup_check['remaining'];
                log_registration_attempt($pdo, $ip, $email, 'signup', 'lock', $username);
                echo json_encode(['status' => 'error', 'message' => '', 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
                exit;
            }
            
            if (($res = validate_email($email, $pdo)) !== true) {
                $inc = increment_attempts_signup($pdo, $ip, $email);
                $remaining = $inc['blocked'] ? $inc['remaining'] : 0;
                log_registration_attempt($pdo, $ip, $email, 'signup', 'failed - invalid email', $username);
                echo json_encode(['status' => 'error', 'message' => $res, 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
                exit;
            }
            if (($res = validate_username($username, $pdo)) !== true) {
                $inc = increment_attempts_signup($pdo, $ip, $email);
                $remaining = $inc['blocked'] ? $inc['remaining'] : 0;
                log_registration_attempt($pdo, $ip, $email, 'signup', 'failed - invalid username', $username);
                echo json_encode(['status' => 'error', 'message' => $res, 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
                exit;
            }
            if (($res = validate_password($password, $pdo)) !== true) {
                $inc = increment_attempts_signup($pdo, $ip, $email);
                $remaining = $inc['blocked'] ? $inc['remaining'] : 0;
                log_registration_attempt($pdo, $ip, $email, 'signup', 'failed - invalid password', $username);
                echo json_encode(['status' => 'error', 'message' => $res, 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
                exit;
            }

            $otp_resend_check = rate_limit_gate_otp($pdo, $ip, $email, 'otp_resend', false);
            $otp_verify_check = rate_limit_gate_otp($pdo, $ip, $email, 'otp_verify', false);

            if ($otp_resend_check['blocked'] || $otp_verify_check['blocked']) {
                $msg = $otp_resend_check['message'] ?: $otp_verify_check['message'];
                $msg = preg_replace('/(\d+)/', '<span class="text-red-600 font-bold">$1</span>', $msg);
                echo json_encode(['status' => 'error', 'message' => $msg, 'lock_remaining' => 0, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
                exit;
            }

            rate_limit_reset_signup($pdo, $ip, $email);
            
            $_SESSION["user_email"] = $email;
            $_SESSION["user_username"] = $username;
            $ip_info = get_ip_info($ip);
            $_SESSION["registration_country_code"] = $ip_info['country_code'];
            $_SESSION["user_password_hashed"] = password_hash($password, PASSWORD_DEFAULT);
            
            $otp = random_int(100000, 999999);
            $otp_salt = bin2hex(random_bytes(32));
            $_SESSION["otp_code"] = $otp;
            $_SESSION["otp_hash"] = hash_hmac('sha256', (string)$otp, $otp_salt);
            $_SESSION["otp_salt"] = $otp_salt;
            $_SESSION["otp_ip"] = $ip;
            $_SESSION["otp_fingerprint"] = get_device_fingerprint();
            $_SESSION['otp_expire_time'] = time() + 300;
            $_SESSION['last_otp_time'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
            $_SESSION['csrf_token_time'] = time();

            echo json_encode(['status' => 'success', 'redirect' => 'sendmail.php', 'csrf_token' => $new_csrf_token]);
            exit;

        } catch (PDOException $e) {
            getLogger('database')->critical('Signup Error', ['error' => $e->getMessage()]);
            echo json_encode(['status' => 'error', 'message' => 'Technical error.', 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
            exit;
        }
    }

    // --- معالجة تسجيل الدخول (Login) ---
    if (isset($_POST['login'])) {
        $fingerprint = get_device_fingerprint();
        $ip = getClientIP();
        $login_input = trim($_POST['login_input'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $attempt_check = check_if_blocked_login($pdo, $ip, $login_input);
        if ($attempt_check['blocked']) {
            $remaining = $attempt_check['remaining'];
            if ($remaining <= 0) {
                 preg_match('/(\d+) ثانية/', $attempt_check['message'], $matches);
                 $remaining = isset($matches[1]) ? (int)$matches[1] : 0;
            }
            $stmt = $pdo->prepare("INSERT INTO login_logs (ip, login_input, user_id, device_fingerprint, action, detail, created_at) VALUES (?, ?, ?, ?, 'failed', 'lock', ?)");
            $stmt->execute([$ip, $login_input, null, $fingerprint, time()]);
            
            echo json_encode(['status' => 'error', 'message' => '', 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
            exit;
        }

        if ($login_input === '' || $password === '') {
            $inc = increment_attempts_login($pdo, $ip, $login_input);
            $remaining = $inc['blocked'] ? $inc['remaining'] : 0;
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password', 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, password, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                rate_limit_reset_login($pdo, $ip, $login_input);
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['csrf_token_time'] = time();
                
                $stmt = $pdo->prepare("INSERT INTO login_logs (ip, login_input, user_id, device_fingerprint, action, detail, created_at) VALUES (?, ?, ?, ?, 'login', 'Success', ?)");
                $stmt->execute([$ip, $login_input, $user['id'], $fingerprint, time()]);

                if (!empty($_POST['remember_me'])) set_remember_me($pdo, $user['id']);
    ob_clean(); // <--- أضف هذا السطر لمسح أي مسافات أو أسطر سابقة

                echo json_encode(['status' => 'success', 'redirect' => 'index.php?success=1', 'csrf_token' => $new_csrf_token]);
                exit;
            } else {
                $res = increment_attempts_login($pdo, $ip, $login_input);
                $remaining = $res['blocked'] ? $res['remaining'] : 0;
                
                $stmt = $pdo->prepare("INSERT INTO login_logs (ip, login_input, user_id, device_fingerprint, action, detail, created_at) VALUES (?, ?, ?, ?, 'failed', 'Invalid credentials', ?)");
                $stmt->execute([$ip, $login_input, $user['id'] ?? null, $fingerprint, time()]);
                
                $msg = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
                if($res['blocked']) $msg = ""; 

                echo json_encode(['status' => 'error', 'message' => $msg, 'lock_remaining' => $remaining, 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
                exit;
            }
        } catch (PDOException $e) {
            getLogger('database')->critical('Login DB Error', ['error' => $e->getMessage()]);
            echo json_encode(['status' => 'error', 'message' => 'Technical error.', 'csrf_token' => $new_csrf_token, 'reset_turnstile' => true]);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="تسجيل الدخول إلى حسابك بأمان. انضم إلينا اليوم للوصول إلى أفضل العطور والإكسسوارات.">
    
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://challenges.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <title>Abdolwahab Accssories & Parfums - login </title>
    <link rel="icon" type="image/png" href="">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- استبدل your-domain.com برابط موقعك الحقيقي -->
<link rel="canonical" href="https://www.abdolwahabaccessories.com/register.php">
<!-- Open Graph / Facebook & WhatsApp -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.abdolwahabaccessories.com/register.php">
<meta property="og:title" content="تسجيل الدخول - عبدالوهاب للعطور والإكسسوارات">
<meta property="og:description" content="سجل دخولك الآن للوصول إلى أفخم العطور والإكسسوارات الحصرية.">
<meta property="og:image" content="https://www.abdolwahabaccessories.com/register.php"> <!-- صورة بمقاس 1200x630 -->

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://www.abdolwahabaccessories.com/register.php">
<meta name="twitter:title" content="تسجيل الدخول - عبدالوهاب للعطور والإكسسوارات">
<meta name="twitter:description" content="سجل دخولك الآن للوصول إلى أفخم العطور والإكسسوارات الحصرية.">
<meta name="twitter:image" content="https://www.abdolwahabaccessories.com/register.php">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AccountPage",
  "name": "تسجيل الدخول وإنشاء حساب",
  "description": "صفحة الدخول الآمن لعملاء متجر عبدالوهاب للعطور والإكسسوارات",
  "url": "https://www.abdolwahabaccessories.com/register.php",
  "mainEntity": {
    "@type": "Organization",
    "name": "Abdolwahab Accssories & Parfums",
    "logo": "https://www.your-domain.com/images/lgicon.png",
    "url": "https://www.abdolwahabaccessories.com/register.php/"
  },
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [{
      "@type": "ListItem",
      "position": 1,
      "name": "الرئيسية",
      "item": "https://www.abdolwahabaccessories.com/"
    },{
      "@type": "ListItem",
      "position": 2,
      "name": "تسجيل الدخول",
      "item": "https://www.abdolwahabaccessories.com/register.php"
    }]
  }
}
</script>

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

        /* --- Header Logic --- */
        /* Default (Mobile): Fixed */
        .page-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center; /* Center on mobile */
            background-color: transparent; 
            z-index: 50;
                pointer-events: none; /* <--- هذا السطر الجديد مهم جداً */

        }
        
/* أضف هذا الكلاس الجديد لضمان أن الشعار لا يزال قابلاً للضغط إذا كان رابطاً */
.logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
    direction: ltr; 
    pointer-events: auto; /* <--- السماح بالضغط على الشعار فقط */
}
/* رفع حاوية التسجيل لتكون فوق الهيدر في الطبقات */
.auth-container {
    position: relative;
    z-index: 100 !important;
}
        /* Desktop: Absolute (Not Fixed) & Right Aligned */
        @media (min-width: 768px) {
            .page-header {
                position: absolute; /* Changed from fixed */
                justify-content: flex-end; 
            }
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            direction: ltr; 
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

        .auth-container {
            width: 100%;
            max-width: 440px; 
            padding: 2rem;
            position: relative;
        }

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
        .custom-input:focus {
            outline: none;
            box-shadow: none;
            border-bottom-color: #C8A95A;
        }
        .custom-input::placeholder {
            color: #6b7280; 
            font-size: 0.95rem; 
            transition: color 0.3s;
            font-weight: 500;
        }

        /* --- VISITOR BUTTON UPDATES --- */
        .btn-visitor {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem; 
            border-radius: 9999px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            
            /* Update: Transparent with Black Border */
            background-color: transparent;
            border: 1px solid #000000;
            color: #000000;
            
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden; /* For shine effect */
        }
        
        .btn-visitor svg {
            color: #000000; /* Black Icon */
            transition: color 0.3s ease;
        }

  
        /* Shine Effect for Visitor Button */
        .btn-visitor::after {
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

       @media (hover: hover) and (min-width: 1024px) {
    
    .btn-visitor:hover {
        background-color: #000000; 
        color: #ffffff;
    }
    
    .btn-visitor:hover svg {
        color: #ffffff;
    }

    .btn-visitor:hover::after {
        animation: shine 0.75s ease-in-out forwards;
    }

    .btn-primary-pro:not(:disabled):hover {
        /* تأثيرات الهوفر للزر الرئيسي */
        background-color: #000; /* أو اللون الذي وضعته */
    }

    .btn-primary-pro:not(:disabled):hover::after {
        animation: shine 0.75s ease-in-out forwards;
    }
}

        /* --- Main Action Button --- */
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
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: skewX(-25deg);
            transition: none; 
        }

       

        @keyframes shine {
            100% {
                left: 150%; 
            }
        }

        .btn-primary-pro:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        .gold-checkbox {
            accent-color: #C8A95A;
            width: 1.1rem; 
            height: 1.1rem;
            cursor: pointer;
        }

        .form-wrapper {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            opacity: 1;
            transform: translateY(0);
        }
        .form-hidden { display: none; }
        .fade-out { opacity: 0; transform: translateY(10px); }
        .fade-in { opacity: 0; transform: translateY(-10px); animation: fadeInAnim 0.3s forwards; }
        @keyframes fadeInAnim { to { opacity: 1; transform: translateY(0); } }

        .error-box { background-color: transparent; border: none; padding: 0.5rem 0; margin-bottom: 0.5rem; width: 100%; }

    </style>
    <meta name="robots" content="index, follow, max-image-preview:large">
</head>
<body>

    <noscript>
        <div style="padding: 20px; text-align: center; color: red;">
            جافا سكريبت معطل في متصفحك. يرجى تفعيله لاستخدام هذا الموقع.
        </div>
    </noscript>

    <!-- Header -->
    <header class="page-header">
        <div class="logo-container">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1765686271/wmremove-transformed_2_1_roya1b.jpg" alt="شعار عبدالوهاب للعطور - Abdolwahab Parfums Logo" class="logo-img">
            <div class="logo-text-group font-logo">
                <span class="logo-main">Abdolwahab</span>
                <span class="logo-sub">Accessories & Parfums</span>
            </div>
        </div>
    </header>

   <main class="auth-container">

        <button id="loginTab" class="hidden"></button>
        <button id="signupTab" class="hidden"></button>

        <!-- LOGIN FORM -->
        <div id="loginForm" class="form-wrapper">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 font-cairo tracking-tight">تسجيل الدخول</h1>
            </div>

            <div class="error-box hidden" id="login-error-box">
                <p class="text-sm text-red-600 text-center font-medium" id="login-error-msg"></p>
                <p class="text-sm text-center text-gray-500 mt-1" id="login-timer-msg"></p>
            </div>
   <a href="index.php?v=visitor" class="btn-visitor mb-6" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 100%; position: relative; z-index: 9999;">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
    <span>الدخول كزائر</span>
</a>
            <form class="space-y-6" method="POST" id="form-login">
                <input type="hidden" name="login" value="1">
                
           

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="flex-shrink-0 mx-4 text-gray-600 text-sm font-bold uppercase tracking-wider">أو</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="screen_resolution" value="">
                <input type="hidden" name="timezone" value="">
                <input type="hidden" name="browser_plugins" value="">
               
                <div class="space-y-5">
                    <div>
                        <input name="login_input" id="login-username" type="text" class="custom-input" placeholder="اسم المستخدم أو البريد الإلكتروني">
                    </div>

                    <div>
                        <input name="password" id="login-password" type="password" class="custom-input" placeholder="كلمة المرور">
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember_me" class="gold-checkbox transition"> 
                        <span class="text-base text-gray-700 lg:hover:text-black transition-colors font-medium">تذكرني</span>
                    </label>
                    <a href="forgot_password.php" class="text-base text-gray-700 lg:hover:text-black transition-colors font-medium">نسيت كلمة المرور؟</a>
                </div>

                <input type="hidden" name="cf-turnstile-response" class="turnstile-response-input" value="">
             <!-- مكان الكابتشا لصفحة الدخول -->
<div id="turnstile-container-login" class="flex justify-center my-3 scale-90"></div>

                <!-- Main Login Button -->
                <button type="submit" id="loginSubmitBtn" class="btn-primary-pro" disabled>
                    تسجيل الدخول
                </button>

                <p class="text-center text-base text-gray-700 mt-8">
                    ليس لديك حساب؟ 
                    <a href="#" onclick="switchAuth('loginForm', 'signupForm'); return false;" class="font-bold lg:hover:underline transition-all" style="color:#C8A95A;">إنشاء حساب جديد</a>
                </p>
            </form>
        </div>

        <!-- SIGNUP FORM -->
        <div id="signupForm" class="form-wrapper form-hidden">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 font-cairo tracking-tight">إنشاء حساب</h1>
            </div>

            <div class="error-box hidden" id="signup-error-box">
                <p class="text-sm text-red-600 text-center font-medium" id="signup-error-msg"></p>
                <p class="text-sm text-center text-gray-500 mt-1" id="signup-timer-msg"></p>
            </div>
<!-- نموذج مستقل خاص بالزائر فقط لا علاقة له بالجافا سكريبت -->
<a href="index.php?v=visitor" class="btn-visitor mb-6" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 100%; position: relative; z-index: 9999;">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
    <span>الدخول كزائر</span>
</a>
            <form class="space-y-6" method="POST" id="form-signup">
                <input type="hidden" name="sign-up" value="1">
                
        

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="flex-shrink-0 mx-4 text-gray-600 text-sm font-bold uppercase tracking-wider">أو</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="screen_resolution" value="">
                <input type="hidden" name="timezone" value="">
                <input type="hidden" name="browser_plugins" value="">
                
                <div class="space-y-5">
                    <div>
                        <input name="username" id="signup-name" type="text" class="custom-input" placeholder="اسم المستخدم">
                    </div>

                    <div>
                        <input name="email" id="signup-email" type="email" class="custom-input" placeholder="البريد الإلكتروني">
                    </div>

                    <div>
                        <input name="password" id="signup-password" type="password" class="custom-input" placeholder="كلمة المرور">
                    </div>
                </div>

                <input type="hidden" name="cf-turnstile-response" class="turnstile-response-input" value="">
                <!-- مكان الكابتشا لصفحة التسجيل -->
<div id="turnstile-container-signup" class="flex justify-center my-3 scale-90"></div>
                <!-- Main Signup Button -->
                <button type="submit" id="signupSubmitBtn" class="btn-primary-pro" disabled>
                    إنشاء حساب
                </button>

                <p class="text-center text-base text-gray-700 mt-8">
                    لديك حساب بالفعل؟ 
                    <a href="#" onclick="switchAuth('signupForm', 'loginForm'); return false;" class="font-bold lg:hover:underline transition-all" style="color:#C8A95A;">تسجيل الدخول</a>
                </p>
            </form>
        </div>

    </main>

   <script>
    // متغيرات لحفظ معرفات الكابتشا
    let loginWidgetId = null;
    let signupWidgetId = null;
    let timerTimeout = null;

    // مفتاح الموقع الخاص بكلاود فلير (يأتي من الـ PHP)
    const siteKey = "<?php echo htmlspecialchars($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']); ?>";

    // 1. دالة تهيئة الكابتشا (تعمل تلقائياً عند تحميل الصفحة)
    function initTurnstile() {
        // تهيئة كابتشا تسجيل الدخول
        if (document.getElementById('turnstile-container-login')) {
            loginWidgetId = turnstile.render('#turnstile-container-login', {
                sitekey: siteKey,
                callback: function(token) {
                    // عند النجاح: ضع التوكن في حقل اللوجين وفعل الزر
                    const form = document.getElementById('form-login');
                    if(form) form.querySelector('input[name="cf-turnstile-response"]').value = token;
                    document.getElementById('loginSubmitBtn').disabled = false;
                },
                'expired-callback': function() {
                    document.getElementById('loginSubmitBtn').disabled = true;
                }
            });
        }

        // تهيئة كابتشا إنشاء الحساب
        if (document.getElementById('turnstile-container-signup')) {
            signupWidgetId = turnstile.render('#turnstile-container-signup', {
                sitekey: siteKey,
                callback: function(token) {
                    // عند النجاح: ضع التوكن في حقل التسجيل وفعل الزر
                    const form = document.getElementById('form-signup');
                    if(form) form.querySelector('input[name="cf-turnstile-response"]').value = token;
                    document.getElementById('signupSubmitBtn').disabled = false;
                },
                'expired-callback': function() {
                    document.getElementById('signupSubmitBtn').disabled = true;
                }
            });
        }
    }

    // استدعاء دالة التهيئة عند تحميل الصفحة
    window.onload = function() {
        // ننتظر قليلاً لضمان تحميل مكتبة Turnstile
        if (typeof turnstile !== 'undefined') {
            initTurnstile();
        } else {
            // محاولة أخرى في حال تأخر التحميل
            setTimeout(initTurnstile, 1000);
        }
        
        // إعدادات الشاشة والمتصفح
        document.querySelectorAll('input[name="screen_resolution"]').forEach(i => i.value = `${window.screen.width}x${window.screen.height}`);
        document.querySelectorAll('input[name="timezone"]').forEach(i => i.value = Intl.DateTimeFormat().resolvedOptions().timeZone);
        document.querySelectorAll('input[name="browser_plugins"]').forEach(i => i.value = Array.from(navigator.plugins).map(p=>p.name).join(','));
    };

    // 2. دوال العداد والتبديل (كما هي مع تحسينات بسيطة)
    function formatTime(totalSeconds) {
        if (totalSeconds < 0) totalSeconds = 0;
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function startDynamicTimer(seconds, msgBoxId, btnId) {
        if (timerTimeout) clearTimeout(timerTimeout);
        let remain = parseInt(seconds);
        const msgBox = document.getElementById(msgBoxId);
        const btn = document.getElementById(btnId);
        
        if (!msgBox || !btn) return;
        
        btn.disabled = true;
        
        function tick() {
            if (remain <= 0) {
                btn.disabled = false; // ملاحظة: سيظل الزر معطلاً إذا لم يتم حل الكابتشا، وهذا صحيح
                // التحقق من الكابتشا قبل التفعيل الكامل
                const formId = btnId === 'loginSubmitBtn' ? 'form-login' : 'form-signup';
                const token = document.getElementById(formId).querySelector('input[name="cf-turnstile-response"]').value;
                if(!token) btn.disabled = true; 

                msgBox.innerHTML = '<span class="text-green-600 font-bold">يمكنك المحاولة الآن</span>';
                return;
            }
            const formatted = formatTime(remain);
            msgBox.innerHTML = `يرجى الانتظار <span class='text-red-600 font-bold'>${formatted}</span> قبل المحاولة مرة أخرى.`;
            remain--;
            timerTimeout = setTimeout(tick, 1000);
        }
        tick();
    }

    function switchAuth(hideId, showId) {
        const hideEl = document.getElementById(hideId);
        const showEl = document.getElementById(showId);
        hideEl.classList.add('fade-out');
        setTimeout(() => {
            hideEl.classList.add('form-hidden');
            hideEl.classList.remove('fade-out'); 
            showEl.classList.remove('form-hidden');
            showEl.classList.add('fade-in');
            setTimeout(() => { showEl.classList.remove('fade-in'); }, 300);
        }, 300); 
    }

    // 3. دالة المعالجة الرئيسية (AJAX) - تم إصلاح منطق إعادة التعيين
    async function handleAuth(event, formId, errorBoxId, errorMsgId, timerMsgId, submitBtnId) {
        event.preventDefault();
        const form = document.getElementById(formId);
        const submitBtn = document.getElementById(submitBtnId);
        const errorBox = document.getElementById(errorBoxId);
        const errorMsg = document.getElementById(errorMsgId);
        const timerMsg = document.getElementById(timerMsgId);
        
        const originalText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = 'جاري المعالجة...';
        errorBox.classList.add('hidden');
        errorMsg.innerHTML = ''; 
        timerMsg.innerHTML = ''; 

        const formData = new FormData(form);

        try {
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            // تحديث CSRF Token دائماً
            if (data.csrf_token) {
                document.querySelectorAll('input[name="csrf_token"]').forEach(el => el.value = data.csrf_token);
            }

            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else {
                errorBox.classList.remove('hidden');
                submitBtn.innerText = originalText;
                
                if (data.lock_remaining && data.lock_remaining > 0) {
                    errorMsg.innerHTML = ''; 
                    startDynamicTimer(data.lock_remaining, timerMsgId, submitBtnId);
                } else {
                    errorMsg.innerHTML = data.message;
                    // لا نفعل الزر فوراً، ننتظر إعادة حل الكابتشا
                }
                
                // --- إصلاح مشكلة الكابتشا ---
                if (data.reset_turnstile && window.turnstile) {
                    // تصفير الحقل المخفي الخاص بهذا الفورم فقط
                    form.querySelector('input[name="cf-turnstile-response"]').value = '';

                    // إعادة تعيين الكابتشا المحددة فقط باستخدام الـ ID
                    if (formId === 'form-login' && loginWidgetId) {
                        turnstile.reset(loginWidgetId);
                    } else if (formId === 'form-signup' && signupWidgetId) {
                        turnstile.reset(signupWidgetId);
                    }

                    // تعطيل الزر لإجبار المستخدم على الحل مرة أخرى
                    submitBtn.disabled = true;
                }
            }
        } catch (error) {
            console.error(error);
            errorMsg.innerHTML = "حدث خطأ في الاتصال.";
            errorBox.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    }

    document.getElementById('form-login').addEventListener('submit', (e) => handleAuth(e, 'form-login', 'login-error-box', 'login-error-msg', 'login-timer-msg', 'loginSubmitBtn'));
    document.getElementById('form-signup').addEventListener('submit', (e) => handleAuth(e, 'form-signup', 'signup-error-box', 'signup-error-msg', 'signup-timer-msg', 'signupSubmitBtn'));

</script>
</body>
</html>
