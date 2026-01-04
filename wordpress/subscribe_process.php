<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

// دالة موحدة للرد لضمان تجديد التوكن والكابتشا دائماً
function send_final_response($status, $message) {
    // توليد توكن جديد للعملية القادمة لضمان التزامن
    $new_token = generate_csrf_token(); 
    
    // حفظ الجلسة فوراً قبل إرسال الرد للمتصفح
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    echo json_encode([
        'status' => $status,
        'message' => $message,
        'new_token' => $new_token
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_final_response('error', 'طلب غير مسموح.');
}

// 1. فحص الـ CSRF
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    send_final_response('error', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى.');
}

// 2. فحص البريد الإلكتروني
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    send_final_response('error', 'البريد الإلكتروني المدخل غير صحيح.');
}

// 3. فحص الـ Rate Limit (من config.php)
$config = include('config.php');
$limits = $config['rate_limits']['subscribe'];
$ip = getClientIP();

try {
    $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM ip_attemptss WHERE ip = ? AND action_type = 'subscribe' LIMIT 1");
    $stmt->execute([$ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $time_passed = time() - $row['last_attempt'];
        $current_attempts = ($time_passed > $limits['interval']) ? 0 : $row['attempts'];
        if ($current_attempts >= $limits['attempts']) {
            send_final_response('error', 'لقد تجاوزت حد المحاولات المسموح به (3 محاولات في الساعة).');
        }
    }
} catch (PDOException $e) {}

// 4. التحقق من كابتشا Turnstile (توكن الكابتشا صالح لمرة واحدة فقط)
$turnstile_token = $_POST['cf-turnstile-response'] ?? '';
if (empty($turnstile_token) || !validate_turnstile_response($turnstile_token)) {
    send_final_response('error', 'فشل تحقق الأمان (Captcha). يرجى المحاولة مرة أخرى.');
}

// 5. تسجيل زيادة المحاولات في الـ Rate Limit
try {
    $stmt = $pdo->prepare("INSERT INTO ip_attemptss (ip, action_type, attempts, last_attempt, updated_at) 
        VALUES (?, 'subscribe', 1, UNIX_TIMESTAMP(), NOW()) 
        ON DUPLICATE KEY UPDATE 
        attempts = IF(UNIX_TIMESTAMP() - last_attempt > ?, 1, attempts + 1),
        last_attempt = UNIX_TIMESTAMP(),
        updated_at = NOW()");
    $stmt->execute([$ip, $limits['interval']]);
} catch (PDOException $e) {}

// 6. تنفيذ عملية الاشتراك
try {
    // هل الإيميل موجود مسبقاً؟
    $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['has_subscribed'] = true;
        send_final_response('success', 'أنت مشترك بالفعل في قائمتنا البريدية.');
    }

    // إدخال جديد (يدعم الزوار والمسجلين)
    $stmt = $pdo->prepare("INSERT INTO subscribers (email, ip_address, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$email, $ip]);

    $_SESSION['has_subscribed'] = true;
    send_final_response('success', 'تم اشتراكك بنجاح! شكراً لثقتك.');

} catch (PDOException $e) {
    send_final_response('error', 'حدث خطأ فني أثناء حفظ البيانات.');
}