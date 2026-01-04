<?php

// الخطوة 1: استدعاء إعدادات Monolog أولاً وقبل كل شيء
require_once __DIR__ . '/logger_setup.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// --- تحميل متغيرات البيئة (keys.env) ---
if (!file_exists(__DIR__ . '/keys.env')) {
    // إذا كان الملف غير موجود، قم بتسجيل خطأ فادح وأوقف التنفيذ
    getLogger('setup')->critical('FATAL ERROR: keys.env file not found.', ['path' => __DIR__]);
    http_response_code(503); // Service Unavailable
    exit();
}

try {
    $dotenv = Dotenv::createImmutable(__DIR__, 'keys.env');
    $dotenv->load();
} catch (Exception $e) {
    // إذا فشل تحميل الملف، قم بتسجيل الخطأ وأوقف التنفيذ
    getLogger('setup')->critical('Failed to load keys.env file.', ['error' => $e->getMessage()]);
    http_response_code(503); // Service Unavailable
    exit();
}

// --- إعدادات الاتصال بقاعدة البيانات ---
$host = $_ENV['SMTP_DBHOST'] ?? null;
$dbname = $_ENV['SMTP_DBNAME'] ?? null;
$username = $_ENV['SMTP_DBUSERNAME'] ?? null;
$password = $_ENV['SMTP_DBPASSWORD'] ?? null;

// خيارات PDO لتحسين الأمان والتعامل مع الأخطاء (تبقى كما هي، فهي ممتازة)
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// --- إنشاء اتصال PDO ---
try {
    // إنشاء الاتصال
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    
    // ✅ (اختياري لكن احترافي) تسجيل نجاح الاتصال على مستوى DEBUG إذا أردت
   // getLogger('database')->debug('Database connection established successfully.');

} catch (PDOException $e) {
    // ✅ الخطوة 2: التعامل مع فشل الاتصال بشكل آمن واحترافي
    
    // 1. تسجيل الخطأ الحقيقي والتفصيلي للمطورين فقط باستخدام Monolog
    $dbLogger = getLogger('database');
    $dbLogger->critical('DATABASE CONNECTION FAILED.', [
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage() 
        // ملاحظة: لا تقم بتسجيل كلمة المرور أو معلومات الاتصال الكاملة هنا
    ]);

    // 2. عرض رسالة عامة وآمنة للمستخدم وإيقاف التنفيذ
    http_response_code(503); // 503 Service Unavailable هو الرمز الأنسب
    // يمكنك هنا عرض صفحة HTML كاملة للأخطاء
}
?>