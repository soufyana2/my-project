<?php

// الخطوة 1: استدعاء إعدادات Monolog أولاً وقبل كل شيء
require_once __DIR__ . '/logger_setup.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// --- تحميل متغيرات البيئة (keys.env) من المجلد الأب ---
// نستخدم dirname(__DIR__) للوصول من مجلد wordpress إلى مجلد my-project
$envPath = dirname(__DIR__); 

if (!file_exists($envPath . '/keys.env')) {
    // إذا كان الملف غير موجود، قم بتسجيل خطأ فادح وأوقف التنفيذ
    getLogger('setup')->critical('FATAL ERROR: keys.env file not found.', ['path' => $envPath]);
    http_response_code(503); // Service Unavailable
    exit("Technical error: Configuration file missing.");
}

try {
    // نقوم بتمرير المسار الأب واسم الملف للمكتبة
    $dotenv = Dotenv::createImmutable($envPath, 'keys.env');
    $dotenv->load();
} catch (Exception $e) {
    // إذا فشل تحميل الملف، قم بتسجيل الخطأ وأوقف التنفيذ
    getLogger('setup')->critical('Failed to load keys.env file.', ['error' => $e->getMessage()]);
    http_response_code(503); // Service Unavailable
    exit("Technical error: Configuration load failed.");
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
    global $pdo; 
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    $dbLogger = getLogger('database');
    $dbLogger->critical('DATABASE CONNECTION FAILED.', [
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage() 
    ]);

    http_response_code(503);
    // السطر الناقص هو السطر التالي لإيقاف تنفيذ الموقع تماماً عند فشل الاتصال
    die("Technical error: Database connection failed. Please check the logs."); 
}
?>