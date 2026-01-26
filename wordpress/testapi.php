<?php
require_once 'vendor/autoload.php';
use Automattic\WooCommerce\Client;

$url = 'http://localhost:8088/my-project/wordpress/wordpress_latest/wordpress';
$ck = 'ck_25478fc009729ed7f9147a5e0af3f74f30ae49ce';
$cs = 'cs_25099c3b8f8d2811474cb971e11471efac5fe27b';

// تفعيل ميزة الـ Debug لرؤية الرد الخام
$woocommerce = new Client($url, $ck, $cs, [
    'version' => 'wc/v3',
    'verify_ssl' => false,
    'query_string_auth' => true // مهم جداً لبعض سيرفرات Localhost
]);

try {
    $products = $woocommerce->get('products', ['per_page' => 1]);
    echo "<h1>الاتصال ناجح! ✅</h1>";
} catch (\Exception $e) {
    echo "<h1>فشل الاتصال ❌</h1>";
    echo "<h3>الرسالة:</h3> " . $e->getMessage();
    
    // هذا الجزء سيظهر لك ما الذي أرسله السيرفر فعلياً (HTML أو خطأ)
    echo "<h3>الرد القادم من السيرفر (Raw Response):</h3>";
    echo "<pre style='background: #eee; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow: auto;'>";
    echo htmlspecialchars($woocommerce->http->getResponse()->getBody());
    echo "</pre>";
}