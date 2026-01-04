<?php
ob_start();
// --- إضافة في بداية functions.php ---
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // إعدادات الكوكيز قبل بدء الجلسة
        session_set_cookie_params([
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
    
    global $pdo;
    // إذا لم يكن هناك مستخدم مسجل، نفحص "تذكرني"
    if (!isset($_SESSION['user_id'])) {
        check_remember_me($pdo);
    }
}
// تشغيل الجلسة فوراً
secure_session_start();

function get_user_cache($key) {
    $user_id = $_SESSION['user_id'] ?? 'guest_' . session_id();
    $cache_file = __DIR__ . "/cache/user_{$user_id}_{$key}.json";
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 300)) { // كاش لمدة 5 دقائق
        return json_decode(file_get_contents($cache_file), true);
    }
    return null;
}

function set_user_cache($key, $data) {
    $user_id = $_SESSION['user_id'] ?? 'guest_' . session_id();
    if (!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755);
    $cache_file = __DIR__ . "/cache/user_{$user_id}_{$key}.json";
    file_put_contents($cache_file, json_encode($data));
}
require_once 'logger_setup.php';

// sign up functions 
function validate_email($email, $pdo) {
    $authLogger = getLogger('auth');
    $generic_error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    
    // تنظيف البريد الإلكتروني لمنع الحقن
    $email = filter_var(strtolower(trim($email)), FILTER_SANITIZE_EMAIL);
    
    // التحقق من أن التنظيف لم يؤدِ إلى بريد غير صالح
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $generic_error;
    }

    // التحقق من الطول
    if (strlen($email) > 255) {
        return $generic_error;
    }

    // التحقق من النطاقات الممنوعة
    $forbidden_domains = [
        'tempmail.com', '10minutemail.com', 'yopmail.com', 'mailinator.com',
        'maildrop.cc', 'fakeemail.com', 'trashmail.com'
    ];

    $email_parts = explode('@', $email);
    if (count($email_parts) !== 2) {
        return $generic_error;
    }

    $email_domain = strtolower($email_parts[1]);
    if (in_array($email_domain, $forbidden_domains)) {
        return $generic_error;
    }

    // فحص MX Records
    list($user, $domain) = explode('@', $email, 2);
    if (!checkdnsrr($domain, 'MX')) {
        return $generic_error;
    }

    try { // Monolog ADDED: أضفنا try-catch لتسجيل أخطاء قاعدة البيانات
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $authLogger->info('Email already exists.', ['email' => $email, 'ip' => getClientIP()]);
            return $generic_error;
        }
    } catch (PDOException $e) {
        $dbLogger = getLogger('database');
        $dbLogger->error('Failed to check for existing email.', ['error' => $e->getMessage()]);
        return "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
    }


    return true;
}




function validate_username($username, $pdo) {
           $authLogger = getLogger('auth');
    $securityLogger = getLogger('security');

    $original_username = $username; // نحتفظ بالاسم الأصلي لأغراض التسجيل
    $generic_error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
    // تحويل الاسم إلى lowercase وحذف الفراغات الجانبية
    $username = strtolower(trim($username));

   
    // التحقق من الطول المناسب
    if (strlen($username) < 6 || strlen($username) > 20) {
                $authLogger->notice( ' Username verification failed: Invalid length.', ['username_attempt' => $original_username]);

        return $generic_error;
    }


    // السماح فقط بالحروف الصغيرة، الأرقام، _ - .
  if (!preg_match('/^[a-z0-9]+([._][a-z0-9]+)*$/', $username)) {
        $authLogger->notice( 'Username verification failed: Contains disallowed characters.', ['username_attempt' => $original_username, 'ip' => getClientIP()]);
        return $generic_error;
}

    // قائمة الأسماء المحظورة
    $forbidden_usernames = [
        'admin', 'administrator', 'root', 'superadmin', 'super_admin', 'moderator', 'mod', 'staff',
        'support', 'sysadmin', 'system', 'owner', 'webmaster', 'manager', 'teamlead', 'leadmod',
        'official', 'realadmin', 'verified', 'adminteam', 'security', 'supportteam',
        'test', 'demo', 'beta', 'alpha', 'trial', 'preview', 'bot', 'api', 'server', 'dev',
        'developer', 'noreply', 'no-reply', 'norep1y', 'n0reply', 'systemmessage',
        'fuck', 'shit', 'bitch', 'asshole', 'dick', 'bastard', 'slut', 'whore',
        'nigger', 'nigga', 'faggot', 'cunt', 'prick', 'douche', 'damn',
        'fuk', 'fck', 'sh1t', 'sh!t', 'biatch', 'd1ck', 'a55', 'a_s_s', 'fag', 'niga', 'n1gga',
        'anonymous', 'anon', 'null', 'undefined', 'unknown', 'user', 'guest',
        'none', 'nobody', 'default', 'temp', 'temporary', 'sample', 'testuser',
        'facebook', 'instagram', 'twitter', 'google', 'youtube', 'tiktok',
        'snapchat', 'whatsapp', 'linkedin', 'reddit', 'pinterest', 'telegram',
        'spotify', 'netflix', 'discord', 'paypal', 'amazon', 'apple', 'icloud', 'microsoft',
        'admin1', 'adm1n', 'admln', 'admín', 'админ', 'админка', 'администратор',
        'admin_', '_admin', 'admin123', 'admin321', 'admin01', 'superadmin1',
        'mod1', 'moderator1', 'moder4tor', 'm0derator', 'mod123', 'm0d', 'staff1', 'st@ff',
        '4dm1n', '4dmin', 'adm!n', 'adm1n_', 'admin-', 'adm*n', 'admln_', 'support_', 'lokopoco12',
        'sʏsadmin', '𝚊𝚍𝚖𝚒𝚗'
    ];

    // توحيد القائمة إلى lowercase فقط احتياطًا
    $forbidden_usernames = array_map(function($u) {
        return strtolower(trim($u));
    }, $forbidden_usernames);

    // التحقق من وجود الاسم في القائمة
    if (in_array($username, $forbidden_usernames)) {
        $securityLogger->warning('Attempted use of a banned username.', ['username_attempt' => $original_username, 'ip' => getClientIP()]);
        return $generic_error;
    }
      
     try {
        // التحقق من تكرار الاسم في قاعدة البيانات
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            // هذا فشل تحقق عادي
            $authLogger->info('Verification failed: The username already exists.', ['username_attempt' => $original_username]);
            return $generic_error;
        }
    } catch (PDOException $e) {
        // تسجيل أخطاء قاعدة البيانات في قناتها الخاصة
        $dbLogger = getLogger('database');
        $dbLogger->error('Failed to query the username in the database. ', ['error' => $e->getMessage()]);
        // يمكنك أيضًا الاحتفاظ بـ error_log إذا أردت
        return "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
    }

    return true; // كل شيء سليم ✅
}
function validate_password($password) {
    $securityLogger = getLogger('security');
    $generic_error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    $common_passwords = ['qwertyui','asdfghjk','zxcvbnmm','password','12345678','superman','admin123'];

    $password = trim($password);
    $password_length = strlen($password);

  if ($password_length < 8) {
        $securityLogger->notice(' Password verification failed: Too short..', ['length_provided' => $password_length]);
        return $generic_error;
    }
    if ($password_length > 128) {
        $securityLogger->notice('Password verification failed: Too long.', ['length_provided' => $password_length]);
        return $generic_error;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $securityLogger->notice('Password verification failed: Does not contain an uppercase letter.');
        return $generic_error;
    }
    if (!preg_match('/[a-z]/', $password)) {
        $securityLogger->notice('Password verification failed: Does not contain a lowercase letter.');
        return $generic_error;
    }
    if (!preg_match('/[0-9]/', $password)) {
        $securityLogger->notice('Password verification failed: Does not contain a number.');
        return $generic_error;
    }
  
 // السماح فقط بالحروف الإنجليزية، الأرقام، والرموز المسموح بها
    if (!preg_match('/^[a-zA-Z0-9!@#$%^&*()\-_=+\[\]{};:,.<>?\/]+$/', $password)) {
        $securityLogger->warning('Password verification failed: Contains invalid or harmful characters.');
        return $generic_error;
    }

    // تحقق من وجود رمز خاص في الوسط فقط إذا كانت كلمة المرور قصيرة (<12)
    if (strlen($password) < 12) {
        $middle = substr($password, 1, -1);
        if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:,.<>?\/]/', $middle)) {
        $securityLogger->notice('Verification failed: The short password does not contain a special character in the middle.');
        return $generic_error;
        }
    }
    if (in_array(strtolower($password), array_map('strtolower', $common_passwords))) {
        $securityLogger->warning('Attempted use of a very common and weak password.');
        return $generic_error;
    }

    return true; // كلمة المرور صالحة
}



     // otp function
     
 function validate_otp($otpcode) {
        $authLogger = getLogger('auth');
    $generic_error = "رمز التحقق غير صالح.";
    $otpcode = trim($otpcode);

    // التحقق من إدخال OTP
    if (empty($otpcode)) {
        return "يرجى إدخال رمز التحقق.";
    }

    // التحقق من أن OTP مكون من 6 أرقام
    if (!preg_match('/^\d{6}$/', $otpcode)) {
        $authLogger->warning('validate_otp: OTP code is not 6 digits.', ['otp_attempt' => $otpcode]);
        return $generic_error;
    }

    // التحقق من وجود بيانات الجلسة
    if (empty($_SESSION['otp_hash']) || empty($_SESSION['otp_salt']) || empty($_SESSION['otp_expire_time'])) {
        $authLogger->error('validate_otp: Missing session variables for OTP validation.');
        return $generic_error;
    }

    // التحقق من انتهاء صلاحية OTP
    if (time() > $_SESSION['otp_expire_time']) {
        $authLogger->warning('validate_otp: OTP expired.', ['expired_at' => $_SESSION['otp_expire_time'], 'current_time' => time()]);
        return "انتهت صلاحية رمز التحقق.";
    }

    // التحقق من الهاش
    if (hash_hmac('sha256', $otpcode, $_SESSION['otp_salt']) !== $_SESSION['otp_hash']) {
        $authLogger->warning('validate_otp: Hash mismatch for OTP.', ['otp_attempt' => $otpcode]);
        return $generic_error;
    }

    // إذا نجحت جميع الفحوصات
    $authLogger->info('validate_otp: OTP verification successful.');
    return true;
}










// csrf token systeme

/**
 * إنشاء توكن CSRF جديد
 */
function generate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من صلاحية التوكن وتجديده إذا لزم الأمر
 */
function manage_csrf_token() {
    // إذا لم يوجد توكن أو انتهت صلاحيته (أكثر من ساعة)
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time'] > 3600)) {
 // Monolog ADDED: تسجيل عملية تجديد التوكن
        $securityLogger = getLogger('security');
        $reason = !isset($_SESSION['csrf_token']) ? 'not_set' : 'expired';
        $securityLogger->info('CSRF token regenerated.', ['reason' => $reason, 'ip' => getClientIP()]);
        generate_csrf_token();
    }
}

/**
 * التحقق من صحة CSRF (نسخة محسنة)
 */
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

            $securityLogger = getLogger('security');
            $securityLogger->critical('CSRF Validation Failed', ['ip' => getClientIP(), 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A']);
            
            http_response_code(403);
            
            if (is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'طلب غير صالح (CSRF).']);
                exit;
            } else {
                include('csrf_error.php');
                exit;
            }
        }
        
        // ✅ تجديد التوكن بعد كل تحقق ناجح
        generate_csrf_token();
    }
}

/**
 * التحقق إذا كان الطلب AJAX
 */
function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}








































// إنشاء توكن وتخزينه في DB والكوكيز
function set_remember_me($pdo, $user_id) {
        $authLogger = getLogger('auth');
    try {
        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        $expire_ts = time() + 60*60*24*14; // تقليل إلى 14 يومًا
        $expire_sql = gmdate('Y-m-d H:i:s', $expire_ts); // ✅ تم التعديل إلى gmdate
        $ip = getClientIP(); // إضافة IP
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, selector, hashed_token, expires_at, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $selector, $hashed_token, $expire_sql, $ip]);
        $cookie_value = base64_encode($selector . ':' . $token);
        setcookie('remember_token', $cookie_value, [
            'expires' => $expire_ts,
            'path' => '/',
            'domain' => '',
            'secure' => true, // ستغيره إلى true لاحقًا
            'httponly' => true,
            'samesite' => 'strict' // ستغيره إلى Strict لاحقًا
        ]);
        $authLogger->info('Remember me cookie set.', ['user_id' => $user_id, 'selector' => $selector, 'ip' => $ip]);
    } catch (PDOException $e) {
        getLogger('database')->error('set_remember_me: Database error', ['error' => $e->getMessage()]);
    } catch (Exception $e) {
        getLogger('general')->error('set_remember_me: General error', ['error' => $e->getMessage()]);
    }
}

// التحقق من الكوكي باستخدام selector و validator و IP
function check_remember_me($pdo) {
        $authLogger = getLogger('auth');
    if (isset($_SESSION['user_id'])) {
        return false;
    }
    if (empty($_COOKIE['remember_token'])) {
        return false;
    }
    $cookie_data = base64_decode($_COOKIE['remember_token'], true);
    if (!$cookie_data || substr_count($cookie_data, ':') !== 1) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        return false;
    }
    list($selector, $validator) = explode(':', $cookie_data, 2);
    $current_ip = getClientIP(); // الحصول على IP الحالي
    $authLogger->info('Attempting to log in via remember me cookie.', ['selector' => $selector, 'ip' => $current_ip]);
    try {
        $stmt = $pdo->prepare("SELECT rt.id, rt.user_id, rt.hashed_token, rt.expires_at, rt.ip, u.username, u.email 
                               FROM remember_tokens rt 
                               JOIN users u ON rt.user_id = u.id 
                               WHERE rt.selector = ?");
        $stmt->execute([$selector]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tokenRow) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return false;
        }
        // التحقق من تطابق IP
        if ($tokenRow['ip'] !== $current_ip) {
            clear_remember_me($pdo, $tokenRow['user_id'], $selector);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return false;
        }
        if (strtotime($tokenRow['expires_at']) < time()) {
            clear_remember_me($pdo, $tokenRow['user_id'], $selector);
            return false;
        }
if (!password_verify($validator, $tokenRow['hashed_token'])) {
            // إذا فشل التحقق، نحذف هذا التوكن المحدد كإجراء وقائي
            clear_remember_me($pdo, $tokenRow['user_id'], $selector);
            // ونحذف الكوكي من المتصفح
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            $authLogger->warning('Failed remember me login due to invalid validator.', ['user_id' => $tokenRow['user_id'], 'selector' => $selector, 'ip' => $current_ip]);
            return false;
        }

        // --- ✨ بداية التعديل الأمني ---

        // الخطوة 1: حذف التوكن القديم فوراً بعد استخدامه بنجاح
        clear_remember_me($pdo, $tokenRow['user_id'], $selector);

        // الخطوة 2: نجاح: استعادة الجلسة (هذا الجزء يبقى كما هو)
        $_SESSION['user_id'] = $tokenRow['user_id'];
        $_SESSION['username'] = $tokenRow['username'];
        $_SESSION['user_email'] = $tokenRow['email'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['login_ip'] = $current_ip;

        // الخطوة 3: إصدار توكن "تذكرني" جديد تماماً وإرساله في كوكي جديد
        // هذا يضمن أن الكوكي القديم لا يمكن استخدامه مرة أخرى
        set_remember_me($pdo, $tokenRow['user_id']);
        
        $authLogger->info('Session restored and token rotated via remember me.', ['user_id' => $tokenRow['user_id'], 'username' => $tokenRow['username']]);
        return true;

        // --- نهاية التعديل الأمني ---

    } catch (PDOException $e) {
        getLogger('database')->error('check_remember_me: Database error.', ['error' => $e->getMessage()]);
        return false;
    }
}
// مسح التوكن من DB والكوكيز

function clear_remember_me($pdo, $user_id, $selector = null) {
    try {
        if ($selector) {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND selector = ?");
            $stmt->execute([$user_id, $selector]);
        } else {
            // لا تحذف الكل تلقائياً، بل فقط التوكن الحالي إذا وجد، أو لا تفعل شيئاً
        }
    } catch (Exception $e) {
        getLogger('database')->error('clear_remember_me: Database error.', ['error' => $e->getMessage()]);
    }
}










      // check if to switch between pages 
      
// هل المستخدم مسجل دخول؟
function isLoggedIn($pdo = null) {
    // التحقق فقط من وجود user_id في الجلسة
    // لا تقم بأي منطق معقد هنا مثل التجديد أو الحذف، اترك ذلك لملف index.php
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
// لمنع وصول المسجلين لصفحات التسجيل/الدخول
// 1. تعديل دالة التحقق من تسجيل الدخول
function redirectIfLoggedIn($redirectUrl = 'index.php') {
    global $pdo;
    if (isLoggedIn($pdo)) {
        if (is_ajax_request()) {
            // في حالة AJAX نرسل أمر توجيه بصيغة JSON
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'redirect' => $redirectUrl]);
            exit();
        }
        header("Location: $redirectUrl");
        exit();
    }
}
// لمنع وصول غير المسجلين لصفحات خاصة (مثل صفحة الملف الشخصي)
function redirectIfNotLoggedIn($redirectUrl = 'register.php') {
    global $pdo;
    if (!isLoggedIn($pdo)) {
        header("Location: $redirectUrl");
        exit();
    }
}









// get user ip address


function getClientIP(): string {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = $_SERVER[$k];
            if ($k === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $ip);
                $ip = trim($parts[0]);
            }
            return normalize_ip($ip); // إضافة normalize_ip هنا للتوحيد
        }
    }
    return '127.0.0.1';
}









// ملف إعدادات حدود Rate Limit
require_once 'config.php'; // يجب إنشاء هذا الملف كما هو موضح لاحقًا

function normalize_ip(string $ip): string {
    return $ip === '::1' ? '127.0.0.1' : (filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '');
}

function get_device_fingerprint(): string {
    $data = [
        $_SERVER['HTTP_USER_AGENT'] ?? '', // المتصفح ونظام التشغيل
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', // لغة المتصفح (عادةً ثابتة)
        getClientIP(), // استخدام getClientIP المُعالج بدلاً من REMOTE_ADDR
    ];
    return hash('sha256', implode('|', $data));
}

function lock_duration(int $attempts, string $action_type): int {
        return match ($action_type){
        // 🔐 Login
    'login' => match (true) {
        $attempts >= 7 => 350, // ~6 دقائق
        $attempts >= 6 => 150, // دقيقتان ونصف
        $attempts >= 5 => 100, // دقيقة و40 ثانية
        default        => 0,
    },

    // 📝 Signup
    'signup' => match (true) {
        $attempts >= 5 => 300, // 5 دقائق
        $attempts >= 4 => 120, // دقيقتان
        $attempts >= 3 => 60,  // دقيقة
        default        => 0,
    },
        'forgot', 'reset' => match (true) {
            // الترتيب مهم: من الأعلى إلى الأدنى
            $attempts >= 5 => 450,  // 5 دقائق (بعد 3 محاولات)
            $attempts >= 4 => 150,  // 5 دقائق (بعد 3 محاولات)
            $attempts >= 3 => 60,   // 1 دقيقة (بعد محاولتين)
            default        => 0,
        },
        'otp_resend' => match (true) {
            $attempts >= 3 => 180,   // مثال: قفل بعد 4 محاولات
            $attempts >= 2=> 80,
            default        => 0,
        },

        'otp_verify' => match (true) {
            $attempts >= 5 => 200,  // مثال: قفل بعد 6 محاولات
            $attempts >= 3 => 80,
            default        => 0,
        },

        default => 0,
    };
}

function lock_duration_ip(int $attempts, string $action_type): int {
    global $pdo;
    $ip = normalize_ip(getClientIP());
    if (!$ip) return 0;

    $config = include('config.php');
    $daily_limit = $config['rate_limits'][$action_type]['daily_limit'] ?? 50;

    $stmt = $pdo->prepare("SELECT SUM(attempts) as daily_count FROM ip_attemptss WHERE ip = ? AND action_type = ? AND last_attempt > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY))");
    try {
        $stmt->execute([$ip, $action_type]);
        $daily_attempts = (int)($stmt->fetchColumn() ?? 0);
    } catch (PDOException $e) {
        getLogger('database')->error('Failed to count daily IP attempts.', ['ip' => $ip, 'action' => $action_type, 'error' => $e->getMessage()]);
        return 0;
    }

    if ($daily_attempts >= $daily_limit) {
        $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, timestamp, expiry) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)");
        try {
            $stmt->execute([$ip, "Exceeded $action_type daily attempts ($daily_attempts)"]);
        } catch (PDOException $e) {
            getLogger('database')->error('Failed to insert into blocked_ips.', ['ip' => $ip, 'action' => $action_type, 'error' => $e->getMessage()]);
        }
        return -1; // حظر لمدة يوم
    }

    return lock_duration($attempts, $action_type); // استخدام نفس منطق lock_duration
}

function check_if_blocked_login(PDO $pdo, string $ip, string $login_input): array {
        $securityLogger = getLogger('security');
    $config = include('config.php');
    $max_attempts = $config['rate_limits']['login']['attempts'] ?? 20;
    $interval = $config['rate_limits']['login']['interval'] ?? 3600; // ساعة واحدة
    $daily_limit = $config['rate_limits']['login']['daily_limit'] ?? 50;

    $ip = normalize_ip($ip);
    if (!$ip) {
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }
    $fingerprint = get_device_fingerprint();
    $now = time();
    $table = 'login_attempts';

    // فحص الحظر العالمي لـ IP
    try {
        $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip = ? AND expiry < NOW()");
$stmt->execute([$ip]);
        $stmt = $pdo->prepare("SELECT expiry FROM blocked_ips WHERE ip = ? AND expiry > NOW() LIMIT 1");
        $stmt->execute([$ip]);
        $block_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($block_row) {
            $remaining = strtotime($block_row['expiry']) - $now;
            $reason = urlencode($block_row['reason'] ?? 'Daily Limit Exceeded'); // تشفير السبب

            // ============ MODIFICATION: إعادة توجيه فورية للحظر الصلب ============
            $securityLogger->critical('IP permanently blocked, redirecting to hard block page.', ['ip' => $ip, 'remaining_seconds' => $remaining]);
         if (is_ajax_request()) {
    // نرسل success ليقوم الجافاسكريبت بالتوجيه فوراً
    echo json_encode(['status' => 'success', 'redirect' => 'ip_blocked.php?reason=' . ($reason ?? '')]); 
    exit;
}
header("Location: ip_blocked.php?reason=" . ($reason ?? ''));
exit;
            // =====================================================================
            
        }
    } catch (PDOException $e) {
            getLogger('database')->error('error in  blocked_ips.', ['ip' => $ip, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }

    // فحص القفل التصاعدي عبر login_attempts
    try {
        $stmt = $pdo->prepare("SELECT id, attempts, locked_until, last_attempt FROM $table WHERE ip = ? AND login_input = ? AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$ip, $login_input, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = 0;
        $locked_until = 0;
        $last_attempt = 0;

        if ($row) {
            $attempts = (int)$row['attempts'];
            $locked_until = (int)$row['locked_until'];
            $last_attempt = (int)$row['last_attempt'];

            // إعادة تعيين إذا مرت الساعة
            if ($now - $last_attempt > $interval) {
                $attempts = 0;
                $locked_until = 0;
                $stmt = $pdo->prepare("UPDATE $table SET attempts = 0, locked_until = 0, updated_at = ? WHERE id = ?");
                $stmt->execute([$now, $row['id']]);
            }
        }

        // إذا كان محظورًا
        if ($locked_until > $now) {
            $remaining = $locked_until - $now;
                   // ✅ تم حذف السطر الخاص بـ $time_arabic وتبسيط الرسالة
            return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => "محاولات تسجيل الدخول محظورة مؤقتًا. يرجى الانتظار."];
        }

        // التحقق من عدد المحاولات
        if ($attempts >= $max_attempts) {
            $remaining = $interval - ($now - $last_attempt);
   return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => "لقد تجاوزت الحد الأقصى للمحاولات. يرجى الانتظار."];        }
    } catch (PDOException $e) {
        getLogger('database')->error('error in ilogin_attempts ', ['ip' => $ip, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    // فحص الحد اليومي عبر ip_attemptss
    try {
        $daily_interval = 86400;
        $stmt = $pdo->prepare("SELECT SUM(attempts) as ip_count FROM ip_attemptss WHERE ip = ? AND action_type = 'login' AND last_attempt > ?");
        $stmt->execute([$ip, $now - $daily_interval]);
        $ip_count = (int)$stmt->fetchColumn();

        if ($ip_count >= $daily_limit) {
            $remaining = $daily_interval ;
            $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, timestamp, expiry) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)");
            $stmt->execute([$ip, "تجاوز الحد اليومي لتسجيل الدخول ($ip_count)", $remaining]);
            $securityLogger->warning('IP blocked for exceeding daily login limit.', ['ip' => $ip, 'daily_count' => $ip_count]);
            return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => $ip_count, 'ip_locked' => true, 'message' => "لقد تجاوزت الحد الأقصى للمحاولات. يرجى الانتظار."];
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error in ip_attemptss', ['error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    return ['blocked' => false, 'remaining' => 0, 'attempts' => $attempts, 'ip_attempts' => $ip_count ?? 0, 'ip_locked' => false, 'message' => ''];
}

function increment_attempts_login(PDO $pdo, string $ip, string $login_input): array {
    $securityLogger = getLogger('security');
    $securityLogger->info('Incrementing failed login attempt.', ['ip' => $ip, 'login_input' => $login_input]);
    
    $config = include('config.php');
    $country_code = adjust_punishment($config, $ip, 'login');
    $max_attempts = $config['rate_limits']['login']['attempts'] ?? 20;
    $interval = $config['rate_limits']['login']['interval'] ?? 3600;
    $daily_limit = $config['rate_limits']['login']['daily_limit'] ?? 50;

    $ip = normalize_ip($ip);
    if (!$ip) {
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }
    $fingerprint = get_device_fingerprint();
    $now = time();
    $table = 'login_attempts';

    // زيادة attempts في login_attempts
    try {
        $stmt = $pdo->prepare("SELECT id, attempts, locked_until, last_attempt FROM $table WHERE ip = ? AND login_input = ? AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$ip, $login_input, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = 0;
        $locked_until = 0;
        $last_attempt = 0;
        $id = null;

        if ($row) {
            $id = (int)$row['id'];
            $attempts = (int)$row['attempts'];
            $locked_until = (int)$row['locked_until'];
            $last_attempt = (int)$row['last_attempt'];

            if ($now - $last_attempt > $interval) {
                $attempts = 0;
                $locked_until = 0;
            }
        }

       // الكود الجديد والمعدل
$attempts += 1;

// ✨--- بداية التعديل ---✨
$duration = 0;
// تحقق: هل هذه المحاولة ستصل أو تتجاوز الحد الأقصى؟
if ($attempts >= $max_attempts) {
    // نعم، طبق الحظر الطويل (ساعة) مباشرة
    $duration = $interval;
} else {
    // لا، استمر في استخدام الحظر التصاعدي
    $duration = lock_duration($attempts, 'login');
}
$locked_until = $duration > 0 ? $now + $duration : 0;
// ✨--- نهاية التعديل ---✨
        if ($id !== null) {
            $stmt = $pdo->prepare("UPDATE $table SET attempts = ?, last_attempt = ?, locked_until = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$attempts, $now, $locked_until, $now, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO $table (ip, login_input, device_fingerprint, attempts, last_attempt, locked_until, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ip, $login_input, $fingerprint, $attempts, $now, $locked_until, $now]);
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error in increment login_attempts', ['error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    // زيادة ip_attempts
    try {
        $daily_interval = 86400;
        $stmt = $pdo->prepare("SELECT SUM(attempts) as ip_count FROM ip_attemptss WHERE ip = ? AND action_type = 'login' AND last_attempt > ?");
        $stmt->execute([$ip, $now - $daily_interval]);
        $ip_count = (int)$stmt->fetchColumn() + 1; // +1 للزيادة الحالية

        $stmt = $pdo->prepare("INSERT INTO ip_attemptss (ip, action_type, device_fingerprint, attempts, last_attempt, locked_until, updated_at, reason) VALUES (?, 'login', ?, 1, ?, 0, ?, 'محاولة تسجيل دخول') ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = ?, updated_at = ?");
        $stmt->execute([$ip, $fingerprint, $now, $now, $now, $now]);

             if ($ip_count >= $daily_limit) {
            // المدة المتبقية هي يوم كامل
            $remaining = 86400; 

            // نقوم بإدراج الحظر في قاعدة البيانات
            $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, timestamp, expiry) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)");
            $stmt->execute([$ip, "Exceeded daily signup attempts ($ip_count)"]);

            
    // ✅ بداية التعديل: إعادة التوجيه الفوري
    getLogger('security')->critical('IP permanently blocked (Login), redirecting to hard block page.', ['ip' => $ip, 'daily_count' => $ip_count]);
if (is_ajax_request()) {
    // نرسل success ليقوم الجافاسكريبت بالتوجيه فوراً
    echo json_encode(['status' => 'success', 'redirect' => 'ip_blocked.php?reason=' . ($reason ?? '')]); 
    exit;
}
header("Location: ip_blocked.php?reason=" . ($reason ?? ''));
exit;
    // ✅ نهاية التعديل
            // ===================================================
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error in increment login_attempts', ['error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    $remaining = ($locked_until > $now ? $locked_until - $now : 0);
    $message = $remaining > 0 ? "محاولات تسجيل الدخول محظورة. يرجى الانتظار." : "";
    return ['blocked' => $remaining > 0, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => $ip_count - 1, 'ip_locked' => false, 'message' => $message];
}






function check_if_blocked_signup(PDO $pdo, string $ip, string $email): array {
    $config = include('config.php');
    $max_attempts = $config['rate_limits']['signup']['attempts'] ?? 5;
    $interval = $config['rate_limits']['signup']['interval'] ?? 3600;
    $daily_limit = $config['rate_limits']['signup']['daily_limit'] ?? 50;

    $ip = normalize_ip($ip);
    if (!$ip) {
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }
    $fingerprint = get_device_fingerprint();
    $now = time();
    $table = 'registration_attempts';

    // فحص الحظر العالمي لـ IP
    try {
        $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip = ? AND expiry < NOW()");
        $stmt->execute([$ip]);
        $stmt = $pdo->prepare("SELECT expiry FROM blocked_ips WHERE ip = ? AND expiry > NOW() LIMIT 1");
        $stmt->execute([$ip]);
        $block_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($block_row) {
            $remaining = strtotime($block_row['expiry']) - $now;
            $reason = urlencode($block_row['reason'] ?? 'Daily Signup Limit Exceeded'); // تشفير السبب

            // ============ MODIFICATION: إعادة توجيه فورية للحظر الصلب ============
            getLogger('security')->critical('IP permanently blocked (Signup), redirecting to hard block page.', ['ip' => $ip, 'remaining_seconds' => $remaining]);
          if (is_ajax_request()) {
    // نرسل success ليقوم الجافاسكريبت بالتوجيه فوراً
    echo json_encode(['status' => 'success', 'redirect' => 'ip_blocked.php?reason=' . ($reason ?? '')]); 
    exit;
}
header("Location: ip_blocked.php?reason=" . ($reason ?? ''));
exit;
            // =====================================================================
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error in blocked_ips check.', ['ip' => $ip, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }

    // فحص القفل التصاعدي عبر registration_attempts
    try {
        $stmt = $pdo->prepare("SELECT id, attempts, locked_until, last_attempt FROM $table WHERE ip = ? AND email = ? AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$ip, $email, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = 0;
        $locked_until = 0;
        $last_attempt = 0;

        if ($row) {
            $attempts = (int)$row['attempts'];
            $locked_until = (int)$row['locked_until'];
            $last_attempt = (int)$row['last_attempt'];

            // إعادة تعيين إذا مرت الساعة
            if ($now - $last_attempt > $interval) {
                $attempts = 0;
                $locked_until = 0;
                $stmt = $pdo->prepare("UPDATE $table SET attempts = 0, locked_until = 0, updated_at = ? WHERE id = ?");
                $stmt->execute([$now, $row['id']]);
            }
        }

        // إذا كان محظورًا
        if ($locked_until > $now) {
            $remaining = $locked_until - $now;
            return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => "محاولات التسجيل محظورة مؤقتًا. يرجى الانتظار."];
        }

        // التحقق من عدد المحاولات
        if ($attempts >= $max_attempts) {
            $remaining = $interval - ($now - $last_attempt);
            return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => "لقد تجاوزت الحد الأقصى للمحاولات. يرجى الانتظار."];
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error checking registration_attempts.', ['ip' => $ip, 'email' => $email, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }
 
    // فحص الحد اليومي عبر ip_attemptss
    try {
        $daily_interval = 86400;
        $stmt = $pdo->prepare("SELECT SUM(attempts) as ip_count FROM ip_attemptss WHERE ip = ? AND action_type = 'signup' AND last_attempt > ?");
        $stmt->execute([$ip, $now - $daily_interval]);
        $ip_count = (int)$stmt->fetchColumn();

        if ($ip_count >= $daily_limit) {
            $remaining = $daily_interval;
            $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, timestamp, expiry) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)");
            $stmt->execute([$ip, "Daily registration limit exceeded    ($ip_count)"]);
            return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => $ip_count, 'ip_locked' => true, 'message' => "لقد تجاوزت الحد الأقصى للمحاولات. يرجى المحاولة لاحقًا."];
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error checking daily ip_attemptss for signup.', ['ip' => $ip, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    return ['blocked' => false, 'remaining' => 0, 'attempts' => $attempts, 'ip_attempts' => $ip_count ?? 0, 'ip_locked' => false, 'message' => ''];
}

function increment_attempts_signup(PDO $pdo, string $ip, string $email): array {
    $config = include('config.php');
    // إضافة GeoIP هنا: تعديل $config بناءً على IP
    $geo_result = adjust_punishment($config, $ip, 'signup'); // استخدم 'signup' كـ $action_type
    $config = $geo_result['config']; // تحديث $config بالإعدادات المعدلة
    
    $max_attempts = $config['rate_limits']['signup']['attempts'] ?? 5;
    $interval = $config['rate_limits']['signup']['interval'] ?? 3600;
    $daily_limit = $config['rate_limits']['signup']['daily_limit'] ?? 50;

    $ip = normalize_ip($ip);
    if (!$ip) {
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }
    $fingerprint = get_device_fingerprint();
    $now = time();
    $table = 'registration_attempts';

    // زيادة attempts في registration_attempts
    try {
        $stmt = $pdo->prepare("SELECT id, attempts, locked_until, last_attempt FROM $table WHERE ip = ? AND email = ? AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$ip, $email, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = 0;
        $locked_until = 0;
        $last_attempt = 0;
        $id = null;

        if ($row) {
            $id = (int)$row['id'];
            $attempts = (int)$row['attempts'];
            $locked_until = (int)$row['locked_until'];
            $last_attempt = (int)$row['last_attempt'];

            if ($now - $last_attempt > $interval) {
                $attempts = 0;
                $locked_until = 0;
            }
        }

// الكود الجديد والمعدل
$attempts += 1;

// ✨--- بداية التعديل ---✨
$duration = 0;
// تحقق: هل هذه المحاولة ستصل أو تتجاوز الحد الأقصى؟
if ($attempts >= $max_attempts) {
    // نعم، طبق الحظر الطويل (ساعة) مباشرة
    $duration = $interval;
} else {
    // لا، استمر في استخدام الحظر التصاعدي
    $duration = lock_duration($attempts, 'signup');
}
$locked_until = $duration > 0 ? $now + $duration : 0;
// ✨--- نهاية التعديل ---✨
        if ($id !== null) {
            $stmt = $pdo->prepare("UPDATE $table SET attempts = ?, last_attempt = ?, locked_until = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$attempts, $now, $locked_until, $now, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO $table (ip, email, device_fingerprint, attempts, last_attempt, locked_until, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ip, $email, $fingerprint, $attempts, $now, $locked_until, $now]);
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error incrementing registration_attempts.', ['ip' => $ip, 'email' => $email, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => 0, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    // زيادة ip_attempts
    try {
        $daily_interval = 86400;
        $stmt = $pdo->prepare("SELECT SUM(attempts) as ip_count FROM ip_attemptss WHERE ip = ? AND action_type = 'signup' AND last_attempt > ?");
        $stmt->execute([$ip, $now - $daily_interval]);
        $ip_count = (int)$stmt->fetchColumn() + 1; // +1 للزيادة الحالية

        $stmt = $pdo->prepare("INSERT INTO ip_attemptss (ip, action_type, device_fingerprint, attempts, last_attempt, locked_until, updated_at, reason) VALUES (?, 'signup', ?, 1, ?, 0, ?, 'signup attempt') ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = ?, updated_at = ?");
        $stmt->execute([$ip, $fingerprint, $now, $now, $now, $now]);

        if ($ip_count >= $daily_limit) {
            $remaining = $daily_interval;
            $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, timestamp, expiry) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)");
            $stmt->execute([$ip, "Daily registration limit exceeded    ($ip_count)"]);
 // ✅ بداية التعديل: إعادة التوجيه الفوري
    getLogger('security')->critical('IP permanently blocked (Signup), redirecting to hard block page.', ['ip' => $ip, 'daily_count' => $ip_count]);
    
   if (is_ajax_request()) {
    // نرسل success ليقوم الجافاسكريبت بالتوجيه فوراً
    echo json_encode(['status' => 'success', 'redirect' => 'ip_blocked.php?reason=' . ($reason ?? '')]); 
    exit;
}
header("Location: ip_blocked.php?reason=" . ($reason ?? ''));
exit;
    // ✅ نهاية التعديل
        }
    } catch (PDOException $e) {
        getLogger('database')->error('Error incrementing ip_attemptss for signup.', ['ip' => $ip, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'remaining' => 0, 'attempts' => $attempts, 'ip_attempts' => 0, 'ip_locked' => false, 'message' => 'A technical error occurred. please try again later.'];
    }

    $remaining = ($locked_until > $now ? $locked_until - $now : 0);
    $message = $remaining > 0 ? "محاولات التسجيل محظورة. يرجى الانتظار." : "";
    return ['blocked' => $remaining > 0, 'remaining' => $remaining, 'attempts' => $attempts, 'ip_attempts' => $ip_count - 1, 'ip_locked' => false, 'message' => $message];
}
function rate_limit_reset_login(PDO $pdo, string $ip, string $login_input): void {
    $ip = normalize_ip($ip);
    if (!$ip || empty($login_input)) {
        getLogger('ratelimit')->error('Invalid input for rate_limit_reset_login.', ['ip' => $ip, 'login_input' => $login_input]);
        return;
    }
    try {
        getLogger('ratelimit')->info('Resetting login attempts.', ['ip' => $ip, 'login_input' => $login_input]);

        // حذف من login_attempts باستخدام login_input
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND login_input = ?");
        $stmt->execute([$ip, $login_input]);
        $rows_deleted_login = $stmt->rowCount();

        /* حذف من ip_attemptss
        $stmt = $pdo->prepare("DELETE FROM ip_attemptss WHERE ip = ? AND action_type = 'login'");
        $stmt->execute([$ip]);
        $rows_deleted_ip = $stmt->rowCount();
*/
    } catch (PDOException $e) {
        getLogger('database')->error('DB error in rate_limit_reset_login.', ['ip' => $ip, 'error' => $e->getMessage()]);
    }
}



function rate_limit_reset_signup(PDO $pdo, string $ip, string $email): void {
    $ip = normalize_ip($ip);
    if (!$ip || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        getLogger('ratelimit')->error('Invalid input for rate_limit_reset_signup.', ['ip' => $ip, 'email' => $email]);
        return;
    }
    $fingerprint = get_device_fingerprint();
    $normalized_email = strtolower($email);
    try {
        // فحص السجلات الموجودة
        $stmt = $pdo->prepare("SELECT ip, email, device_fingerprint FROM registration_attempts WHERE email = ?");
        $stmt->execute([$normalized_email]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        getLogger('ratelimit')->info('Resetting signup attempts.', ['ip' => $ip, 'email' => $email]);

        // محاولة الحذف مع ip وdevice_fingerprint (مشابه لـ rate_limit_reset_otp)
        $stmt = $pdo->prepare("DELETE FROM registration_attempts WHERE ip = ? AND email = ? AND device_fingerprint = ?");
        $stmt->execute([$ip, $normalized_email, $fingerprint]);
        $rows_deleted = $stmt->rowCount();

        // إذا لم يتم حذف أي سجلات، جرب الحذف بالإيميل فقط
        if ($rows_deleted === 0) {
            $stmt = $pdo->prepare("DELETE FROM registration_attempts WHERE email = ?");
            $stmt->execute([$normalized_email]);
        }

        /* حذف من ip_attemptss
        $stmt = $pdo->prepare("DELETE FROM ip_attemptss WHERE ip = ? AND action_type = 'signup'");
        $stmt->execute([$ip]);
        */
    } catch (PDOException $e) {
        getLogger('database')->error('DB error in rate_limit_reset_signup.', ['ip' => $ip, 'email' => $email, 'error' => $e->getMessage()]);
    }
}

function log_registration_attempt(PDO $pdo, string $ip, string $email, string $action, string $detail, string $username = '', ?int $user_id = null): void {
    $ip = normalize_ip($ip);
    if (!$ip || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($action, ['signup'])) {
        getLogger('general')->error('Invalid input to log_registration_attempt.', ['ip' => $ip, 'email' => $email, 'action' => $action]);
        return;
    }
    $now = time();
    $fingerprint = get_device_fingerprint();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO registration_logs (ip, email, username, user_id, device_fingerprint, action, detail, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ip, $email, $username, $user_id, $fingerprint, $action, $detail, $now]);
        getLogger('auth')->info('Registration log stored in DB.', ['ip' => $ip, 'email' => $email, 'action' => $action]);
    } catch (PDOException $e) {
        getLogger('database')->critical('Failed to log registration attempt to DB.', ['ip' => $ip, 'email' => $email, 'error' => $e->getMessage()]);
    }
}






















function is_ip_blocked(PDO $pdo, string $ip): array {
    $ip = normalize_ip($ip);
    $now = time();
    try {
        // حذف السجلات المنتهية
        $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip = ? AND expiry < NOW()");
        $stmt->execute([$ip]);

        // التحقق من وجود حظر
        $stmt = $pdo->prepare("SELECT expiry FROM blocked_ips WHERE ip = ? AND expiry > NOW() LIMIT 1");
        $stmt->execute([$ip]);
        $expiry = $stmt->fetchColumn();

        if ($expiry) {
            return ['blocked' => true, 'remaining' => strtotime($expiry) - $now];
        }

        // التحقق من ip_attemptss لتصفير المحاولات إذا انتهت فترة 24 ساعة
        $stmt = $pdo->prepare("SELECT last_attempt FROM ip_attemptss WHERE ip = ? AND last_attempt > ? LIMIT 1");
        $stmt->execute([$ip, $now - 86400]);
        $last_attempt = $stmt->fetchColumn();
        
        if (!$last_attempt) {
            // تصفير محاولات ip_attemptss إذا مرت 24 ساعة
            $stmt = $pdo->prepare("DELETE FROM ip_attemptss WHERE ip = ?");
            $stmt->execute([$ip]);
            getLogger('ratelimit')->info('IP attempts reset due to inactivity.', ['ip' => $ip]);
        }
    } catch (PDOException $e) {
        getLogger('database')->error('DB error in is_ip_blocked check.', ['ip' => $ip, 'error' => $e->getMessage()]);
    }
    return ['blocked' => false, 'remaining' => 0];
}


// OTP functions rate limit
function rate_limit_gate_otp(PDO $pdo, string $ip, string $login_input, string $action_type, bool $increment = true): array {
    $config = include('config.php');
    $max_attempts = $config['rate_limits'][$action_type]['attempts'] ?? 7;
    $interval = $config['rate_limits'][$action_type]['interval'] ?? 3600;
    $daily_limit = $config['rate_limits'][$action_type]['daily_limit'] ?? 10;
    $cooldown = ($action_type === 'otp_resend') ? ($config['rate_limits'][$action_type]['cooldown'] ?? 60) : 0;

    $now = time();
    $fingerprint = get_device_fingerprint();
    $day_start = $now - 86400;

     // --- ✨ بداية التعديل: فصل التحقق من الحد اليومي لكل نوع إجراء ---
    // تم تغيير الاستعلام ليستخدم action_type = ? بدلاً من IN (...)
    $stmt = $pdo->prepare("SELECT SUM(attempts) as daily_count FROM ip_attemptss WHERE ip = ? AND action_type = ? AND last_attempt > ?");
    // تم إضافة $action_type كمتغير جديد في execute
    $stmt->execute([$ip, $action_type, $day_start]);
    $daily_count = (int)($stmt->fetchColumn() ?? 0);
    // --- نهاية التعديل ---

    if ($daily_count >= $daily_limit) {
        $reason_text = "Exceeded daily OTP attempts ($daily_count)";
        $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, timestamp, expiry) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY)) ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)");
        $stmt->execute([$ip, $reason_text]);
        
        getLogger('security')->critical('IP permanently blocked (OTP), redirecting to hard block page.', [
            'ip' => $ip,
            'action_type' => $action_type,
            'daily_count' => $daily_count
        ]);
        
        if (is_ajax_request()) {
    echo json_encode(['status' => 'success', 'redirect' => 'ip_blocked.php?reason=OTP Daily Limit']);
    exit;
}
header("Location: ip_blocked.php?reason=OTP Daily Limit");
exit;
    }

    // التحقق من محاولات OTP بناءً على otp_attemptsssss (لا تغيير هنا)
    $stmt = $pdo->prepare("SELECT attempts, locked_until, last_attempt FROM otp_attemptsssss WHERE email = ? AND action_type = ? AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$login_input, $action_type, $fingerprint]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $attempts = $row['attempts'] ?? 0;
    $locked_until = $row['locked_until'] ?? 0;
    $last_attempt = $row['last_attempt'] ?? 0;

    if ($last_attempt > 0 && ($now - $last_attempt) > $interval) {
        $attempts = 0;
        $locked_until = 0;
        $stmt = $pdo->prepare("UPDATE otp_attemptsssss SET attempts = 0, locked_until = 0, updated_at = ? WHERE email = ? AND action_type = ? AND device_fingerprint = ?");
        $stmt->execute([$now, $login_input, $action_type, $fingerprint]);
        getLogger('ratelimit')->info('OTP attempts reset for user.', ['email' => $login_input, 'action' => $action_type]);
    }

    // --- ✨ بداية التعديل: التحقق بشكل صحيح من قفل 'otp_verify' عند طلب 'otp_resend' ---
    $interval_remaining = ($attempts >= $max_attempts && $last_attempt > 0) ? ($last_attempt + $interval - $now) : 0;
    
    if ($action_type === 'otp_resend') {
        // جلب حالة otp_verify للربط (مع عدد المحاولات)
        $stmt_verify = $pdo->prepare("SELECT attempts, last_attempt FROM otp_attemptsssss WHERE email = ? AND action_type = 'otp_verify' AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt_verify->execute([$login_input, $fingerprint]);
        $row_verify = $stmt_verify->fetch(PDO::FETCH_ASSOC);
        
        $verify_attempts = (int)($row_verify['attempts'] ?? 0);
        $verify_last_attempt = (int)($row_verify['last_attempt'] ?? 0);
        $max_verify_attempts = $config['rate_limits']['otp_verify']['attempts'] ?? 7;

        // التحقق مما إذا كان 'otp_verify' مقفلاً بسبب تجاوز الحد الأقصى للمحاولات
        if ($verify_attempts >= $max_verify_attempts && $verify_last_attempt > 0) {
            $verify_interval_remaining = max(0, $verify_last_attempt + $interval - $now);
            
            if ($verify_interval_remaining > 0) {
                // استخدام وقت القفل الأطول بين قفل التحقق وقفل إعادة الإرسال
                $final_remaining_time = max($interval_remaining, $verify_interval_remaining);
$message = "إعادة الإرسال غير متاحة حالياً. يرجى الانتظار لمدة <span class='text-red-600 font-bold'>" . gmdate("i:s", $final_remaining_time) . "</span>.";
               getLogger('ratelimit')->notice('Resend blocked due to verify interval lock.', ['email' => $login_input, 'remaining' => $final_remaining_time]);
                
                return [
                    'blocked' => true,
                    'resend_remaining' => $final_remaining_time,
                    'verify_remaining' => $final_remaining_time, // توحيد وقت القفل
                    'attempts' => $attempts,
                    'ip_attempts' => $daily_count,
                    'ip_locked' => false,
                    'message' => $message
                ];
            }
        }
    }
    // --- نهاية التعديل ---

    // حساب الوقت المتبقي بناءً على القفل المؤقت (cooldown) أو القفل الطويل (interval)
    $remaining_duration = max(0, $locked_until - $now, $interval_remaining);

    if ($remaining_duration > 0) {
        $formatted_time = gmdate("i:s", $remaining_duration);
        $message = $action_type === 'otp_resend' ? 
            "إعادة الإرسال محظورة. يرجى الانتظار <span class='text-red-600 font-bold'>{$formatted_time}</span>." :
            "التحقق محظور. يرجى الانتظار <span class='text-red-600 font-bold'>{$formatted_time}</span>.";
        
        getLogger('ratelimit')->notice('OTP action blocked temporarily.', ['email' => $login_input, 'action' => $action_type, 'remaining' => $remaining_duration]);
        return [
            'blocked' => true,
            'resend_remaining' => ($action_type === 'otp_resend') ? $remaining_duration : 0,
            'verify_remaining' => ($action_type === 'otp_verify') ? $remaining_duration : 0,
            'attempts' => $attempts,
            'ip_attempts' => $daily_count,
            'ip_locked' => false,
            'message' => $message
        ];
    }

    if ($increment) {
        increment_attempts_otp($pdo, $ip, $login_input, $action_type);
    }

    return [
        'blocked' => false,
        'resend_remaining' => 0,
        'verify_remaining' => 0,
        'attempts' => $attempts,
        'ip_attempts' => $daily_count,
        'ip_locked' => false,
        'message' => ''
    ];
}
function handle_attempts_sendotp(PDO $pdo, string $ip, string $email): array {
    $res = rate_limit_gate_otp($pdo, $ip, $email, 'otp_resend', false);
    log_otp_attempt($pdo, $ip, $email, 'otp_resend', $res['blocked'] ? 'lock' : 'success');
    if ($res['blocked'] || $res['resend_remaining'] > 0) {
        return $res;
    }
    return [
        'blocked' => false,
        'resend_remaining' => 0,
        'verify_remaining' => 0,
        'attempts' => $res['attempts'] ?? 0,
        'ip_attempts' => $res['ip_attempts'] ?? 0,
        'ip_locked' => $res['ip_locked'] ?? false,
        'message' => ''
    ];
}

function handle_attempts_resend(PDO $pdo, string $ip, string $login_input): array {
    $res = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_resend', false);
    if ($res['blocked'] || $res['resend_remaining'] > 0) {
        log_otp_attempt($pdo, $ip, $login_input, 'otp_resend', 'lock');
        getLogger('ratelimit')->warning('OTP resend blocked by rate limit.', ['email' => $login_input, 'ip' => $ip]);
        return $res;
    }

    $config = include('config.php');
    $max_attempts = $config['rate_limits']['otp_resend']['attempts'] ?? 7;
    $interval = $config['rate_limits']['otp_resend']['interval'] ?? 3600;

    // التحقق من الحد الأقصى للمحاولات
    if ($res['attempts'] >= $max_attempts) {
        $stmt = $pdo->prepare("SELECT last_attempt FROM otp_attemptsssss WHERE email = ? AND action_type = 'otp_resend' AND device_fingerprint = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$login_input, get_device_fingerprint()]);
        $last_attempt = $stmt->fetchColumn() ?? 0;
        $duration = ($last_attempt > 0) ? ($last_attempt + $interval - time()) : $interval;
        $message = "إعادة الإرسال محظورة. يرجى الانتظار <span class='text-red-600 font-bold'>{$duration}</span> ثانية.";
        log_otp_attempt($pdo, $ip, $login_input, 'otp_resend', 'lock');
        getLogger('ratelimit')->warning('OTP resend blocked, max attempts reached.', ['email' => $login_input, 'ip' => $ip, 'attempts' => $res['attempts']]);
        return [
            'blocked' => true,
            'resend_remaining' => $duration,
            'verify_remaining' => 0,
            'attempts' => $res['attempts'],
            'ip_attempts' => $res['ip_attempts'],
            'ip_locked' => $res['ip_locked'],
            'message' => $message
        ];
    }

    // إنشاء OTP جديد
    $otp = random_int(100000, 999999);
    $otp_salt = bin2hex(random_bytes(32));
    $otp_hash = hash_hmac('sha256', (string)$otp, $otp_salt);
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_hash'] = $otp_hash;
    $_SESSION['otp_salt'] = $otp_salt;
    $_SESSION['otp_ip'] = $ip;
    $_SESSION['otp_fingerprint'] = get_device_fingerprint();
    $_SESSION['last_otp_time'] = time();
    $_SESSION['otp_expire_time'] = time() + 300;
    $_SESSION['otp_resend_mode'] = true;
    $_SESSION['user_email'] = $login_input;

    // زيادة عدد المحاولات بعد إنشاء OTP
    increment_attempts_otp($pdo, $ip, $login_input, 'otp_resend');
    log_otp_attempt($pdo, $ip, $login_input, 'otp_resend', 'success');
    getLogger('ratelimit')->info('New OTP generated for resend.', ['email' => $login_input, 'ip' => $ip]);

    return [
        'blocked' => false,
        'resend_remaining' => 0,
        'verify_remaining' => 0,
        'attempts' => $res['attempts'] + 1,
        'ip_attempts' => $res['ip_attempts'],
        'ip_locked' => $res['ip_locked'],
        'message' => ''
    ];
}
function handle_attempts_verify(PDO $pdo, string $ip, string $login_input, string $otp_code): array {
    $res = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_verify', false);
    if ($res['blocked'] || $res['verify_remaining'] > 0) {
        log_otp_attempt($pdo, $ip, $login_input, 'otp_verify', 'lock');
        return $res;
    }

    $now = time();
    if (!isset($_SESSION['otp_hash']) || !isset($_SESSION['otp_salt']) || !isset($_SESSION['otp_expire_time']) || $now > $_SESSION['otp_expire_time']) {
        increment_attempts_otp($pdo, $ip, $login_input, 'otp_verify');
        log_otp_attempt($pdo, $ip, $login_input, 'otp_verify', 'expired');
        $post_check = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_verify', false);
        if ($post_check['blocked']) {
            return $post_check;
        }
        return [
            'blocked' => false,
            'resend_remaining' => 0,
            'verify_remaining' => 0,
            'attempts' => $res['attempts'] + 1,
            'ip_attempts' => $res['ip_attempts'],
            'ip_locked' => $res['ip_locked'],
            'message' => '  انتهت صلاحية رمز التحقق. '
        ];
    }

    $otp_validation = validate_otp($otp_code);
    if ($otp_validation !== true) {
        increment_attempts_otp($pdo, $ip, $login_input, 'otp_verify');
        log_otp_attempt($pdo, $ip, $login_input, 'otp_verify', 'failed');
        $post_check = rate_limit_gate_otp($pdo, $ip, $login_input, 'otp_verify', false);
        if ($post_check['blocked']) {
            return $post_check;
        }
        return [
            'blocked' => false,
            'resend_remaining' => 0,
            'verify_remaining' => 0,
            'attempts' => $res['attempts'] + 1,
            'ip_attempts' => $res['ip_attempts'],
            'ip_locked' => $res['ip_locked'],
            'message' => $otp_validation
        ];
    }

    rate_limit_reset_otp($pdo, $ip, $login_input);
    log_otp_attempt($pdo, $ip, $login_input, 'otp_verify', 'success');
    return [
        'blocked' => false,
        'resend_remaining' => 0,
        'verify_remaining' => 0,
        'attempts' => 0,
        'ip_attempts' => 0,
        'ip_locked' => false,
        'message' => 'Otp code successfuly.'
    ];
}

function increment_attempts_otp(PDO $pdo, string $ip, string $email, string $action_type): void {
    $config = include('config.php');
    // إضافة GeoIP هنا: تعديل $config بناءً على IP
    $geo_result = adjust_punishment($config, $ip, $action_type); // استخدم $action_type مثل 'otp_resend'
    $config = $geo_result['config']; // تحديث $config بالإعدادات المعدلة

    $max_attempts = $config['rate_limits'][$action_type]['attempts'] ?? 7;
    $interval = $config['rate_limits'][$action_type]['interval'] ?? 3600;
    $cooldown = ($action_type === 'otp_resend') ? ($config['rate_limits'][$action_type]['cooldown'] ?? 60) : 0;
    $ip = normalize_ip($ip);
    if (!$ip || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        getLogger('ratelimit')->error('Invalid input for increment_attempts_otp.', ['ip' => $ip, 'email' => $email]);
        return;
    }
    $fingerprint = get_device_fingerprint();
    $now = time();
    $table = 'otp_attemptsssss';

    try {
        // زيادة محاولات OTP بناءً على email
        $stmt = $pdo->prepare("
            SELECT id, attempts, locked_until, last_attempt 
            FROM $table 
            WHERE email = ? AND action_type = ? AND device_fingerprint = ? 
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmt->execute([$email, $action_type, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = 0;
        $locked_until = 0;
        $last_attempt = 0;
        $id = null;

        if ($row) {
            $id = (int)$row['id'];
            $attempts = (int)$row['attempts'];
            $locked_until = (int)$row['locked_until'];
            $last_attempt = (int)$row['last_attempt'];

            if ($now - $last_attempt > $interval) {
                $attempts = 0;
                $locked_until = 0;
                $stmt = $pdo->prepare("UPDATE $table SET attempts = 0, locked_until = 0, updated_at = ? WHERE id = ?");
                $stmt->execute([$now, $id]);
                getLogger('ratelimit')->info('OTP attempts reset due to interval.', ['email' => $email, 'action' => $action_type]);
            }
        }

        if ($locked_until > $now) {
            return;
        }
$attempts += 1;

        // --- ✨ بداية التعديل: منطق جديد لحساب مدة القفل ---
        $duration = 0;
        if ($attempts >= $max_attempts) {
            // إذا تم الوصول للحد الأقصى، استخدم القفل الطويل (interval)
            $duration = $interval;
        } elseif ($action_type === 'otp_resend' && isset($_SESSION['is_initial_otp']) && $_SESSION['is_initial_otp'] === true) {
            // لا تقم بأي قفل عند الإرسال الأولي للرمز
            $duration = 0; 
            unset($_SESSION['is_initial_otp']); // قم بإزالة المتغير بعد استخدامه
        } else {
            // في جميع الحالات الأخرى (فشل التحقق أو إعادة الإرسال)، استخدم القفل التصاعدي
            $duration = lock_duration($attempts, $action_type);
            if ($action_type === 'otp_resend') {
                $duration = max($duration, $cooldown);  // تطبيق cooldown الاحترافي
            }
        }

        $locked_until = ($duration > 0) ? ($now + $duration) : 0;
        // --- نهاية التعديل ---

        if ($id !== null) {
            $stmt = $pdo->prepare("UPDATE $table SET attempts = ?, last_attempt = ?, locked_until = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$attempts, $now, $locked_until, $now, $id]);
        // ... باقي الكود يبقى كما هو
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO $table (ip, email, action_type, login_input, device_fingerprint, attempts, last_attempt, locked_until, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$ip, $email, $action_type, $email, $fingerprint, $attempts, $now, $locked_until, $now]);
        }

        // زيادة محاولات IP بشكل مستقل
        $stmt = $pdo->prepare("
            INSERT INTO ip_attemptss (ip, action_type, device_fingerprint, attempts, last_attempt, locked_until, updated_at, reason)
            VALUES (?, ?, ?, 1, ?, 0, ?, ?)
            ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = ?, updated_at = ?, reason = ?
        ");
        $stmt->execute([$ip, $action_type, $fingerprint, $now, $now, "$action_type attempt", $now, $now, "$action_type attempt"]);

        getLogger('ratelimit')->info('Incremented OTP attempts.', ['ip' => $ip, 'email' => $email, 'action' => $action_type, 'new_count' => $attempts]);

        $_SESSION['otp_resend_mode'] = true;
    } catch (PDOException $e) {
        getLogger('database')->error('DB error in increment_attempts_otp.', ['email' => $email, 'action' => $action_type, 'error' => $e->getMessage()]);
    }
}
function log_otp_attempt(PDO $pdo, string $ip, ?string $login_input, string $action, string $status): void {
    $ip = normalize_ip($ip);
    if (!$ip || (is_null($login_input) || !filter_var($login_input, FILTER_VALIDATE_EMAIL)) || !in_array($action, ['otp_resend', 'otp_verify']) || !in_array($status, ['success', 'lock', 'failed', 'expired'])) {
        getLogger('general')->error('Invalid input for log_otp_attempt.', ['ip' => $ip, 'login_input' => $login_input, 'action' => $action, 'status' => $status]);
        return;
    }
    $fingerprint = get_device_fingerprint();
    $now = time();

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM otp_logs WHERE ip = ? AND login_input = ? AND action = ? AND device_fingerprint = ? AND created_at > ?");
        $stmt->execute([$ip, $login_input, $action, $fingerprint, $now - 5]);
        if ($stmt->fetchColumn() > 0) {
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO otp_logs (ip, login_input, action, detail, device_fingerprint, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ip, $login_input, $action, $status, $fingerprint, $now]);
    } catch (PDOException $e) {
        getLogger('database')->critical('Failed to log OTP attempt to DB.', ['ip' => $ip, 'login_input' => $login_input, 'error' => $e->getMessage()]);
    }
}
function rate_limit_reset_otp(PDO $pdo, string $ip, string $email): void {
    $ip = normalize_ip($ip);
    if (!$ip || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        getLogger('ratelimit')->error('Invalid input for rate_limit_reset_otp.', ['ip' => $ip, 'email' => $email]);
        return;
    }
    $fingerprint = get_device_fingerprint();
    $now = time();
    try {
        // تصفير محاولات OTP
        $stmt = $pdo->prepare("DELETE FROM otp_attemptsssss WHERE email = ? AND device_fingerprint = ?");
        $stmt->execute([$email, $fingerprint]);
        getLogger('ratelimit')->info('Full OTP and IP attempts reset after success.', ['ip' => $ip, 'email' => $email]);

        // إعادة تعيين كامل لـ ip_attemptss بعد نجاح OTP (للمستخدمين الحقيقيين)
        $stmt = $pdo->prepare("DELETE FROM ip_attemptss WHERE ip = ? AND action_type IN ('otp_resend', 'otp_verify')");
        $stmt->execute([$ip]);
        getLogger('ratelimit')->info('OTP-related IP attempts reset after success.', ['ip' => $ip, 'email' => $email]);

        // ملاحظة: الحظر في blocked_ips يظل ساريًا، وis_ip_blocked يدير الإعادة اليومية للسجلات الجديدة
    } catch (PDOException $e) {
        getLogger('database')->error('DB error in rate_limit_reset_otp.', ['ip' => $ip, 'email' => $email, 'error' => $e->getMessage()]);
    }
}
























































































$config = include('config.php');
$forbidden_countries = $config['forbidden_countries'] ?? [];
require_once 'vendor/autoload.php'; // لتحميل المكتبة من Composer
use GeoIp2\Database\Reader;

function get_ip_info(string $ip): array {
    $country_db = __DIR__ . '/geoip/GeoLite2-Country.mmdb';
    $city_db = __DIR__ . '/geoip/GeoLite2-City.mmdb';
    /* $anonymous_db = __DIR__ . '/geoip/GeoIP2-Anonymous-IP.mmdb'; // سيُستخدم عند إضافة القاعدة المدفوعة */
    $asn_db = __DIR__ . '/geoip/GeoLite2-ASN.mmdb';
    $info = [
        'country_code' => 'UNKNOWN',
        'country_name' => 'Unknown',
        'is_anonymous' => false,
        'network_type' => 'Regular'
    ];

    // قائمة ASNs معروفة لـ Tor, VPN, Proxy (للنسخة المجانية)
    $anonymous_asns = [
        208312, // Tor Project
        63949,  // Linode (VPN/Hosting)
        20473,  // Choopa (Vultr, VPN)
        9009,   // M247 (VPN/Proxy)
        13335,  // Cloudflare (VPN/Proxy)
        16276,  // OVH (Hosting/VPN)
        212238, // Datacamp (Hosting/CDN)
        24961,  // myLoc (Hosting/Proxy)
        209242, // NordVPN
        212225, // ExpressVPN
        206092, // Surfshark
        396982, // Google Cloud (Hosting/VPN)
        29066,  // VELIANET (Hosting/Proxy)
        51167,  // Contabo (Hosting/VPN)
        42708   // Portlane (VPN)
    ];

    // قائمة IPs معروفة لـ VPN/Proxy/Tor (للنسخة المجانية)
    $known_anonymous_ips = [
        '185.220.100.252' => 'Tor',    // Tor Exit Node
        '45.76.1.1' => 'Tor',          // Tor Exit Node
        '45.33.23.141' => 'VPN',       // Linode VPN
        '103.86.99.100' => 'VPN',      // M247 VPN
        '172.67.0.1' => 'Proxy',       // Cloudflare Proxy
        '198.98.52.100' => 'Proxy',    // Public Proxy
        '185.104.120.1' => 'VPN',      // NordVPN
        '103.231.78.1' => 'VPN',       // ExpressVPN
        '45.77.186.1' => 'Proxy'       // Vultr Proxy
    ];

    try {
        // جلب بيانات الدولة
        $country_reader = new Reader($country_db);
        $country_record = $country_reader->country($ip);
        $info['country_code'] = strtoupper(trim($country_record->country->isoCode ?? 'UNKNOWN'));
        $info['country_name'] = $country_record->country->name ?? 'Unknown';

        // استخدام GeoLite2-City.mmdb للتحقق من Tor/VPN/Proxy (للنسخة المجانية)
        $city_reader = new Reader($city_db);
        $city_record = $city_reader->city($ip);
        $is_anonymous_proxy = $city_record->traits->is_anonymous_proxy ?? false;
        $is_tor_exit_node = $city_record->traits->is_tor_exit_node ?? false;
        $is_hosting_provider = $city_record->traits->is_hosting_provider ?? false;

        // جلب بيانات ASN
        $asn_reader = new Reader($asn_db);
        $asn_record = $asn_reader->asn($ip);
        $asn = $asn_record->autonomousSystemNumber ?? null;
        $asn_name = $asn_record->autonomousSystemOrganization ?? 'Unknown';

        $is_anonymous_asn = $asn && in_array($asn, $anonymous_asns);
        $is_known_anonymous = isset($known_anonymous_ips[$ip]);
        $info['is_anonymous'] = $is_anonymous_proxy || $is_tor_exit_node || $is_hosting_provider || $is_anonymous_asn || $is_known_anonymous;
        $info['network_type'] = $info['is_anonymous'] ? (
            $is_tor_exit_node ? 'Tor' : (
                $is_anonymous_proxy ? 'Proxy' : (
                    $is_known_anonymous ? $known_anonymous_ips[$ip] : (
                        $is_anonymous_asn ? 'VPN' : 'Hosting'
                    )
                )
            )
        ) : 'Regular';

        /* 
        // الكود التالي للقاعدة المدفوعة (GeoIP2-Anonymous-IP.mmdb) - أزل  عند الترقية
        $anonymous_reader = new Reader($anonymous_db);
        $anonymous_record = $anonymous_reader->anonymousIp($ip);
        $is_anonymous = $anonymous_record->isAnonymous ?? false;
        $is_anonymous_vpn = $anonymous_record->isAnonymousVpn ?? false;
        $is_public_proxy = $anonymous_record->isPublicProxy ?? false;
        $is_tor_exit_node = $anonymous_record->isTorExitNode ?? false;
        $is_hosting_provider = $anonymous_record->isHostingProvider ?? false;

        $info['is_anonymous'] = $is_anonymous || $is_anonymous_vpn || $is_public_proxy || $is_tor_exit_node || $is_hosting_provider;
        $info['network_type'] = $info['is_anonymous'] ? (
            $is_tor_exit_node ? 'Tor' : (
                $is_anonymous_vpn ? 'VPN' : (
                    $is_public_proxy ? 'Proxy' : 'Hosting'
                )
            )
        ) : 'Regular';
        // نهاية الكود المدفوع
        */

    } catch (Exception $e) {
        $geoipLogger = getLogger('geoip'); // افتراض وجود قناة جديدة باسم geoip
        getLogger('geoip')->error('GeoIP lookup failed for IP.', ['ip' => $ip, 'error' => $e->getMessage()]);
        // فحص يدوي لـ IPs معروفة (للنسخة المجانية)
        if (isset($known_anonymous_ips[$ip])) {
            $info['is_anonymous'] = true;
            $info['network_type'] = $known_anonymous_ips[$ip];
            $geoipLogger->warning('Hardcoded anonymous IP detected.', ['ip' => $ip, 'type' => $info['network_type']]);
        }
    }

    return $info;
}
function adjust_punishment(array $config, string $ip, string $action_type): array {
    global $forbidden_countries;
    $forbidden_countries = $forbidden_countries ?? ['CN' => 'China', 'RU' => 'Russia']; // ضمان وجود القائمة
    $ip_info = get_ip_info($ip);
    $country_code = strtoupper(trim($ip_info['country_code']));
    $country_name = $ip_info['country_name'];
    $is_anonymous = $ip_info['is_anonymous'];
    $network_type = $ip_info['network_type'];

    $adjusted_config = $config;
    if ($is_anonymous || isset($forbidden_countries[$country_code])) {
        $adjusted_config['rate_limits'][$action_type]['attempts'] = intval($config['rate_limits'][$action_type]['attempts'] / 2);
        $adjusted_config['rate_limits'][$action_type]['interval'] *= 2;
        $adjusted_config['rate_limits'][$action_type]['daily_limit'] = intval($config['rate_limits'][$action_type]['daily_limit'] / 2);
  // Monolog MODIFIED: استبدال error_log
        $securityLogger = getLogger('security');
        $reason = $is_anonymous ? "Anonymous Network ({$network_type})" : "Forbidden Country ({$country_code})";
        $securityLogger->warning('Stricter rate limits applied.', [
            'ip' => $ip,
            'reason' => $reason,
            'action' => $action_type
        ]);
        } else {
 // Monolog ADDED: تسجيل أن الإجراءات عادية
        $securityLogger = getLogger('security');
        $securityLogger->info('Standard rate limits applied.', [
            'ip' => $ip,
            'country' => $country_code,
            'action' => $action_type
        ]);
        }

    return [
        'country_code' => $country_code,
        'country_name' => $country_name,
        'is_anonymous' => $is_anonymous,
        'network_type' => $network_type,
        'config' => $adjusted_config
    ];
}






// captcha 
function validate_turnstile_response(string $token): bool {
    $secretKey = $_ENV['CLOUDFLARE_TURNSTILE_SECRET_KEY'] ?? '';

    // إذا لم يتم توفير التوكن أو المفتاح السري، فشل التحقق
    if (empty($token) || empty($secretKey)) {
        return false;
    }

    $ip = getClientIP();
    
    // بناء بيانات الطلب إلى Cloudflare
    $data = [
        'secret'   => $secretKey,
        'response' => $token,
        'remoteip' => $ip,
    ];

    // إرسال الطلب باستخدام cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // تسجيل خطأ في الاتصال
        getLogger('security')->error('cURL error while verifying Turnstile.', ['error' => curl_error($ch)]);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);

    // التحقق من نجاح الاستجابة
    if ($result && isset($result['success']) && $result['success'] === true) {
        getLogger('security')->info('Turnstile verification successful.', ['ip' => $ip]);
        return true;
    }

    // تسجيل فشل التحقق مع تفاصيل الخطأ
    $error_codes = isset($result['error-codes']) ? implode(', ', $result['error-codes']) : 'none';
    getLogger('security')->warning('Turnstile verification failed.', ['ip' => $ip, 'error_codes' => $error_codes]);
    
    return false;
}





function redirectIfBlocked(PDO $pdo, string $ip): void {
    try {
        $normalized_ip = normalize_ip($ip);
        if (!$normalized_ip) return;

        $stmt_delete = $pdo->prepare("DELETE FROM blocked_ips WHERE ip = ? AND expiry < NOW()");
        $stmt_delete->execute([$normalized_ip]);

        $stmt_check = $pdo->prepare("SELECT reason, expiry FROM blocked_ips WHERE ip = ? AND expiry > NOW() LIMIT 1");
        $stmt_check->execute([$normalized_ip]);
        $block_info = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($block_info) {
            getLogger('security')->warning('Blocked IP attempted to access a page.', [
                'ip' => $normalized_ip,
                'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'reason' => $block_info['reason']
            ]);

            $redirectUrl = "ip_blocked.php?reason=" . urlencode($block_info['reason'] ?? '');
            
            if (is_ajax_request()) {
                // في حالة AJAX نرسل أمر توجيه لصفحة الحظر
                header('Content-Type: application/json');
                echo json_encode(['status' => 'blocked', 'redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }
    } catch (PDOException $e) {
        getLogger('database')->critical('DB error in redirectIfBlocked check.', ['ip' => $ip, 'error' => $e->getMessage()]);
        if (is_ajax_request()) {
             echo json_encode(['status' => 'error', 'message' => "A technical error occurred. please try again later."]);
             exit;
        }
        http_response_code(500);
        exit("A technical error occurred. please try again later.");
    }
}
















// دالة إنشاء التوكن (كاملة دون تغيير)
function create_password_reset_token(PDO $pdo, string $email): ?string {
    $securityLogger = getLogger('security');
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
$expires_at = date('Y-m-d H:i:s', time() + 3600); // ينتج نصًا مثل: '2025-10-24 12:30:00'
            $stmt_insert = $pdo->prepare(
                "INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)"
            );
            $stmt_insert->execute([$email, $token_hash, $expires_at]);

            $securityLogger->info('Reset password token generate success', ['email' => $email, 'ip' => getClientIP()]);
            
            return $token;
        } else {
            $securityLogger->info('Reset password token not founde', ['email' => $email, 'ip' => getClientIP()]);
            return null;
        }

    } catch (PDOException $e) {
        getLogger('database')->error('Resset password failed', ['error' => $e->getMessage()]);
        return null;
    }
}
// دالة التحقق من التوكن (كاملة مع تحديث للحذف عند انتهاء الصلاحية)

function format_remaining_time(int $seconds): string {
    if ($seconds <= 0) {
        return 'لحظات';
    }

    $days = floor($seconds / 86400);
    $seconds %= 86400;
    $hours = floor($seconds / 3600);
    $seconds %= 3600;
    $minutes = floor($seconds / 60);
    $seconds %= 60;

    $parts = [];
    if ($days > 0) $parts[] = "$days يوم";
    if ($hours > 0) $parts[] = "$hours ساعة";
    if ($minutes > 0) $parts[] = "$minutes دقيقة";
    // عرض الثواني فقط إذا لم تكن هناك وحدات أكبر
    if ($seconds > 0 && empty($parts)) $parts[] = "$seconds ثانية";

    return implode(' و ', $parts);
}

/**
 * الدالة الكاملة والنهائية لإدارة حدود محاولات إعادة تعيين كلمة المرور.
 * تتحقق من الحظر اليومي (مع إعادة توجيه)، والحظر بالساعة (interval)، والحظر التصاعدي،
 * وتقوم بزيادة العدادات في كلا الجدولين.
 *
 * @param PDO $pdo
 * @param string $email
 * @param string $action_type 'forgot' or 'reset'
 * @param bool $increment
 * @return array
 */
function handle_password_reset_attempts(PDO $pdo, string $email, string $action_type, bool $increment = false): array {
    // 1. تحميل الإعدادات والمتغيرات الأساسية
    $config = include('config.php');
    $ip = getClientIP();
    // ================== ✨ بداية التعديل: ربط GeoIP هنا ✨ ==================
    // نستدعي دالة فحص الـ IP ونقوم بتحديث إعدادات حدود المحاولات بناءً على النتيجة
    // نمرر لها 'forgot' أو 'reset' كنوع للإجراء
    $geo_result = adjust_punishment($config, $ip, $action_type); 
    $config = $geo_result['config']; // تحديث متغير الإعدادات بالإعدادات الجديدة المشددة إذا لزم الأمر
    // ================== 🏁 نهاية التعديل 🏁 ==================

    $max_progressive_attempts = $config['rate_limits']['password_reset']['attempts'] ?? 3;
    $interval = $config['rate_limits']['password_reset']['interval'] ?? 3600;
    $daily_ip_limit = $config['rate_limits']['password_reset']['daily_limit'] ?? 5;

    $fingerprint = get_device_fingerprint();
    $now = time();

    // 2. التحقق من الحد اليومي وتفعيل الحظر الفوري (إعادة التوجيه)
    try {
        $stmt_daily = $pdo->prepare("SELECT SUM(attempts) FROM ip_attemptss WHERE ip = ? AND action_type IN ('forgot_password', 'reset_password') AND last_attempt > ?");
        $stmt_daily->execute([$ip, $now - 86400]);
        $daily_count = (int) $stmt_daily->fetchColumn();

        // [الإصلاح الرئيسي الأول]: إذا تم تجاوز الحد اليومي، قم بالحظر وإعادة التوجيه فورًا
        if ($daily_count >= $daily_ip_limit) {
            $reason = "Exceeded daily password reset attempts ($daily_count)";
            $stmt_block = $pdo->prepare(
                "INSERT INTO blocked_ips (ip, reason, expiry) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))
                 ON DUPLICATE KEY UPDATE expiry = VALUES(expiry), reason = VALUES(reason)"
            );
            $stmt_block->execute([$ip, $reason]);

            getLogger('security')->critical('IP hard-blocked for exceeding daily password reset limit.', ['ip' => $ip, 'daily_count' => $daily_count]);
            
            // إعادة توجيه فورية إلى صفحة الحظر وإنهاء الكود
           if (is_ajax_request()) {
    echo json_encode(['status' => 'success', 'redirect' => 'ip_blocked.php?reason=' . urlencode("Daily Limit Exceeded")]);
    exit;
}
header("Location: ip_blocked.php?reason=" . urlencode("Daily Limit Exceeded"));
exit;
        }
    } catch (PDOException $e) {
        getLogger('database')->error('DB error during daily password reset check.', ['ip' => $ip, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }

    // 3. التحقق من القفل التصاعدي وقفل الساعة (Interval)
    try {
        $stmt_progressive = $pdo->prepare("SELECT id, attempts, locked_until, last_attempt FROM password_reset_attempts WHERE email = ? AND ip = ? AND device_fingerprint = ? ORDER BY id DESC LIMIT 1");
        $stmt_progressive->execute([$email, $ip, $fingerprint]);
        $row = $stmt_progressive->fetch(PDO::FETCH_ASSOC);

        $attempts = 0;
        $locked_until = 0;
        $last_attempt = 0;
        $id = null;

        if ($row) {
            $id = $row['id'];
            $last_attempt = (int)$row['last_attempt'];

            // تصفير المحاولات فقط إذا مرت فترة الساعة الكاملة
            if ($now > ($last_attempt + $interval)) {
                $attempts = 0;
            } else {
                $attempts = (int)$row['attempts'];
                $locked_until = (int)$row['locked_until'];
            }
        }
        
        // التحقق الأول: هل هناك قفل تصاعدي نشط حاليًا؟
        // ✨ --- بداية التعديل --- ✨
        if ($locked_until > $now) {
             $remaining_time = $locked_until - $now;
             $message = "لقد قمت بمحاولات كثيرة. يرجى الانتظار لمدة "; // رسالة ثابتة
             return ['blocked' => true, 'message' => $message, 'remaining_seconds' => $remaining_time];
        }

        if ($attempts >= $max_progressive_attempts && $last_attempt > 0) {
            $remaining_interval = ($last_attempt + $interval) - $now;
            if ($remaining_interval > 0) {
                $message = "لقد تجاوزت الحد الأقصى للمحاولات. يرجى المحاولة مرة أخرى بعد "; // رسالة ثابتة
                return ['blocked' => true, 'message' => $message, 'remaining_seconds' => $remaining_interval];
            }
        }
        // ✨ --- نهاية التعديل --- ✨


    } catch (PDOException $e) {
        getLogger('database')->error('DB error during progressive password reset check.', ['email' => $email, 'error' => $e->getMessage()]);
        return ['blocked' => true, 'message' => 'A technical error occurred. please try again later.'];
    }

    // 4. زيادة عداد المحاولات (فقط إذا كان $increment = true)
    if ($increment) {
        try {
            $pdo->beginTransaction();
            $new_attempts = $attempts + 1;
            
// الكود الجديد والمعدل
$lock_duration = 0;
// ✨--- بداية التعديل ---✨
// تحقق: هل هذه المحاولة ستصل أو تتجاوز الحد الأقصى؟
if ($new_attempts >= $max_progressive_attempts) {
    // نعم، طبق الحظر الطويل (ساعة) مباشرة بدلاً من الحظر التصاعدي
    $lock_duration = $interval;
} else {
    // لا، استمر في استخدام الحظر التصاعدي العادي
    $lock_duration = lock_duration($new_attempts, $action_type);
}
$new_locked_until = ($lock_duration > 0) ? $now + $lock_duration : 0;
// ✨--- نهاية التعديل ---✨
            
            if ($id) {
                 $stmt_update = $pdo->prepare("UPDATE password_reset_attempts SET attempts = ?, locked_until = ?, last_attempt = ?, action_type = ? WHERE id = ?");
                 $stmt_update->execute([$new_attempts, $new_locked_until, $now, $action_type, $id]);
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO password_reset_attempts (email, ip, device_fingerprint, action_type, attempts, last_attempt, locked_until) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert->execute([$email, $ip, $fingerprint, $action_type, $new_attempts, $now, $new_locked_until]);
            }

            $ip_action_type = ($action_type === 'forgot') ? 'forgot_password' : 'reset_password';
            $stmt_ip = $pdo->prepare("INSERT INTO ip_attemptss (ip, action_type, device_fingerprint, attempts, last_attempt) VALUES (?, ?, ?, 1, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = ?");
            $stmt_ip->execute([$ip, $ip_action_type, $fingerprint, $now, $now]);
            
            $pdo->commit();

            // إذا تسبب هذا التحديث في قفل فوري، أرجع الرسالة
             // ✨ --- بداية التعديل --- ✨
            if ($new_locked_until > $now) {
                $message = "لقد قمت بمحاولات كثيرة. يرجى الانتظار لمدة ";
                return ['blocked' => true, 'message' => $message, 'remaining_seconds' => $new_locked_until - $now];
            }
            // ✨ --- نهاية التعديل --- ✨


        } catch (PDOException $e) {
            $pdo->rollBack();
            getLogger('database')->error('DB error while incrementing password reset attempts.', ['error' => $e->getMessage()]);
            return ['blocked' => true, 'message' => 'A technical error occurred. please try again later.'];
        }
    }
    
    // 5. إذا لم يتم العثور على أي حظر، اسمح بالمرور
    return ['blocked' => false, 'message' => ''];
}
/**
 * دالة لتصفير محاولات القفل التصاعدي بعد تغيير كلمة المرور بنجاح.
 *
 * @param PDO $pdo اتصال قاعدة البيانات.
 * @param string $email البريد الإلكتروني للمستخدم.
 */


/**
 * دالة التحقق من التوكن (نسخة محسنة)
 * تتحقق من صلاحية التوكن وتضمن أنه لم يُستخدم من قبل.
 */
/**
 * دالة التحقق من التوكن (نسخة مصححة ونهائية)
 * تتحقق من صلاحية التوكن من الجدول الصحيح.
 */

function validate_password_reset_token(PDO $pdo, string $token): ?array {
    if (empty($token)) {
        return null;
    }

    $token_hash = hash('sha256', $token);

    try {
        // --- [هذا هو السطر الذي تم إصلاحه] ---
        // تم تغيير اسم الجدول من password_reset إلى password_resets (مع حرف s)
        $stmt = $pdo->prepare(
            "SELECT email, token_hash, expires_at FROM password_resets WHERE token_hash = ? LIMIT 1"
        );
        $stmt->execute([$token_hash]);
        $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset_data) {
            getLogger('security')->warning('Invalid or already used password reset token presented.', ['token_hash_part' => substr($token_hash, 0, 8)]);
            return null;
        }

        if (strtotime($reset_data['expires_at']) < time()) {
            // إذا كان التوكن منتهي الصلاحية، احذفه من قاعدة البيانات
            $stmt_delete = $pdo->prepare("DELETE FROM password_resets WHERE token_hash = ?");
            $stmt_delete->execute([$token_hash]);
            getLogger('security')->info('Expired password reset token was presented and deleted.', ['token_hash_part' => substr($token_hash, 0, 8)]);
            return null;
        }

        return $reset_data;
    } catch (PDOException $e) {
        getLogger('database')->error('DB error during token validation.', ['error' => $e->getMessage()]);
        return null;
    }
}
/**
 * دالة إعادة تعيين كلمة المرور (نسخة محسنة)
 * تحدث كلمة المرور وتحذف التوكن المستخدم في عملية واحدة (Transaction).
 */
function reset_user_password(PDO $pdo, string $email, string $new_password, string $token_hash): bool {
    // التحقق من قوة كلمة المرور أولاً
    if (validate_password($new_password) !== true) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        // تحديث كلمة مرور المستخدم
        $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_user = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt_update_user->execute([$new_password_hashed, $email]);

        // حذف التوكن المستخدم لمنع إعادة استخدامه
        $stmt_delete_token = $pdo->prepare("DELETE FROM password_resets WHERE token_hash = ?");
        $stmt_delete_token->execute([$token_hash]);
        
        // إذا لم يتم حذف أي توكن، فهذا يعني أنه تم استخدامه في طلب آخر (حالة نادرة)
        if ($stmt_delete_token->rowCount() === 0) {
            $pdo->rollBack();
            getLogger('security')->critical('Attempt to reuse a password reset token was blocked.', ['email' => $email]);
            return false;
        }

        $pdo->commit();
        getLogger('auth')->info('User password successfully reset.', ['email' => $email, 'ip' => getClientIP()]);
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        getLogger('database')->error('DB error during password reset process.', ['email' => $email, 'error' => $e->getMessage()]);
        return false;
    }
}
/**
 * دالة لتصفير محاولات إعادة تعيين كلمة المرور بعد نجاح العملية.
 *
 * @param PDO $pdo اتصال قاعدة البيانات.
 * @param string $email البريد الإلكتروني للمستخدم.
 */
function rate_limit_reset_password(PDO $pdo, string $email): void {
    try {
        // حذف جميع سجلات المحاولات الفاشلة لهذا البريد الإلكتروني
        $stmt = $pdo->prepare("DELETE FROM password_reset_attempts WHERE email = ?");
        $stmt->execute([$email]);
        
        // (اختياري) يمكنك تسجيل هذا الإجراء إذا أردت
        getLogger('ratelimit')->info('Password reset attempts cleared after success.', ['email' => $email]);
        
    } catch (PDOException $e) {
        getLogger('database')->error('DB error in rate_limit_reset_password.', ['email' => $email, 'error' => $e->getMessage()]);
    }
}


function renderProductCard($product, $is_skeleton = false) {
    // 1. ضروري جداً جلب المتغير العالمي لكي يعرف الكرت إذا كان المنتج في المفضلة أم لا
    global $user_wishlist_ids; 

    $image_src = "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=";
    $hover_image_src = $image_src;
    $category_name = "غير مصنف";
    $product_title = "";
    $price = "";
    $regular_price = "";
    $skeleton_class = "skeleton-pending";
    $product_link = "#";

    if (!$is_skeleton && is_array($product)) {
        $image_src = !empty($product['images'][0]['src']) ? $product['images'][0]['src'] : "";
        $hover_image_src = (isset($product['images'][1]['src'])) ? $product['images'][1]['src'] : $image_src;
        $category_name = !empty($product['categories'][0]['name']) ? $product['categories'][0]['name'] : "متجرنا";
        $product_title = $product['name'];
        $price = $product['price'] ? number_format($product['price'], 2) . ' د.م' : 'N/A';
        $regular_price = (isset($product['regular_price']) && $product['regular_price'] > $product['price']) ? number_format($product['regular_price'], 2) . ' د.م' : '';
        $product_link = 'product.php?id=' . $product['id'];
    }

    // 2. التحقق من حالة المنتج (نشط أم لا)
    $is_active = (isset($product['id']) && is_array($user_wishlist_ids) && in_array($product['id'], $user_wishlist_ids)) ? 'active' : '';

    echo '
    <div class="product-card cursor-pointer group card-load-animation" style="--card-bg-color: var(--card-one-bg);">
        <div class="relative flex-grow flex flex-col">
            <a href="' . $product_link . '" class="block">
                <div class="image-container ' . $skeleton_class . '" data-main-image-src="' . $image_src . '" data-hover-image-src="' . $hover_image_src . '">
                    <img loading="lazy" src="' . $image_src . '" alt="' . htmlspecialchars($product_title) . '" class="skeleton-image main-product-image">
                    <img loading="lazy" src="' . $hover_image_src . '" alt="Hover" class="skeleton-image hover-product-image">
                    
<button class="wishlist-icon is-hidden ' . $is_active . '" 
        onclick="toggleWishlist(this, event)" 
        data-product-id="' . $product['id'] . '">
    <i class="fa-regular fa-heart icon-empty"></i>
    <i class="fa-solid fa-heart icon-filled"></i>
</button>
                </div>
            </a>
            <div class="mt-auto w-full info-part flex flex-col items-end">
                <p class="text-xs font-bold underline category-gold-beige category-text arabic-font">' . htmlspecialchars($category_name) . '</p>
                <h3 class="product-title text-sm font-bold text-text-dark mt-1 arabic-font">' . htmlspecialchars($product_title) . '</h3>

                <div class="product-price-small">
                    <p class="text-sm price-value arabic-font">' . $price . '</p>';
                    if ($regular_price) {
                        echo '<p class="text-xs old-price arabic-font">' . $regular_price . '</p>';
                    }
                echo '</div>
            </div>
        </div>
    </div>';
}





?>