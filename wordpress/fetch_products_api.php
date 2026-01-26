<?php
// fetch_products_api.php
ob_start();
include("db.php");
require_once 'functions.php'; 
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// إعداد Log بسيط للملف (لأنه مستخدم في الدوال)
$log = new Logger('api_logger');
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/api_errors.log', Logger::WARNING));


// تحقق من وجود ملف keys.env
if (!file_exists(dirname(__DIR__) . '/apikeys.env')) {
        http_response_code(500);
    exit("A technical error occurred. Please try again later. (API keys not found)");
}

try {
    // التصحيح هنا: نخرج مستوى واحد فقط من wordpress إلى my-project
    $root = dirname(__DIR__); 

    // التأكد من اسم الملف، إذا كان اسمه .env نستخدم الحالة الأولى
if (!file_exists(dirname(__DIR__) . '/apikeys.env')) {
            $dotenv = Dotenv::createImmutable($root);
        $dotenv->load();
    } 
    // إذا كان اسمه apikeys.env نستخدم هذه الحالة
    elseif (file_exists(dirname(__DIR__) . '/apikeys.env'))  {
        $dotenv = Dotenv::createImmutable($root, 'apikeys.env');
        $dotenv->load();
    }
} catch (Exception $e) {
    // خطأ في تحميل ملف البيئة
}
$woocommerce = new Client(
    $_ENV['wordpress_url'],
    $_ENV['consumer_key'],
    $_ENV['secret_key'],
    [
        'version' => 'wc/v3',
        'verify_ssl' => false,
        'timeout' => 30
    ]
);

// استلام البرامترات
$type = $_GET['type'] ?? 'category';
$category = $_GET['category'] ?? '';
$per_page = (int)($_GET['per_page'] ?? 4);
$featured = (isset($_GET['featured']) && $_GET['featured'] === 'true');

// جلب البيانات
if ($featured || $type === 'featured') {
    $products = fetchFeaturedProducts($woocommerce, $per_page);
} else {
    $products = fetchProducts($woocommerce, $category, $per_page);
}

// عرض النتائج
if (!empty($products)) {
    foreach ($products as $product) {
        // هذه الدالة موجودة في functions.php
        renderProductCard($product); 
    }
} else {
    echo '<div class="col-span-2 text-center py-10"><p class="text-gray-500">نأسف، لا توجد منتجات حالياً.</p></div>';
}