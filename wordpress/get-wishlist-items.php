<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;
try { if (file_exists(__DIR__ . '/apikeys.env')) { $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env'); $dotenv->load(); } } catch (Exception $e) {}

if (!isset($_SESSION['user_id'])) { exit; }
if (!empty($ids)) {
    try {
        // الطلب هنا
        $products = $woocommerce->get('products', ['include' => $ids, 'status' => 'publish']);
        // ... باقي الكود
    } catch (Exception $e) {
        echo "قائمة المفضلة فارغة حالياً"; // اجعل الرسالة لطيفة بدلاً من "خطأ"
    }
}
$woocommerce = new Automattic\WooCommerce\Client(
    $_ENV['wordpress_url'], $_ENV['consumer_key'], $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false]
);

$stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($ids)) {
    echo '<div class="text-center py-20"><img src="public/images/empty%20wishlist.png" class="w-20 mx-auto opacity-50 mb-4 empty-menu-icon"><h3 class="text-gray-500 font-bold">مفضلتك فارغة حالياً</h3></div>';
} else {
    try {
        $products = $woocommerce->get('products', ['include' => $ids]);
        $products = json_decode(json_encode($products), true);
        
// ابحث عن سطر الـ echo المسؤول عن عدد العناصر واستبدله بهذا:
echo '<div class="text-xs font-bold mb-4 text-right" style="font-size: 14px; font-family: \'Cairo\', sans-serif;">لديك <span id="wishlist-badge-count">'.count($products).'</span> عناصر في قائمة الأمنيات.</div>';
        echo '<div class="space-y-4">';
        foreach ($products as $item) {
            // جلب اسم التصنيف بشكل صحيح
            $category_name = !empty($item['categories']) ? $item['categories'][0]['name'] : 'منتج';

            echo '
            <div class="wishlist-item-row product-card-professional group p-4 flex flex-row items-start gap-5 relative bg-white" data-id="'.$item['id'].'">
                <div class="w-28 h-36 flex-shrink-0 overflow-hidden relative rounded-sm">
                    <div class="w-full h-full bg-cover bg-center" style="background-image: url(\''.$item['images'][0]['src'].'\');"></div>
                    <button onclick="toggleWishlist(this, event)" data-product-id="'.$item['id'].'" class="wishlist-icon remove-product-icon absolute top-2 left-2 w-8 h-8 bg-white/90 text-red-500 rounded-full flex items-center justify-center shadow-sm active">
                        <i class="ph ph-trash text-lg"></i>
                    </button>
                </div>
                <div class="flex flex-col flex-grow min-w-0 h-full justify-between py-1">
                    <div>
                        <h4 class="font-serif font-bold text-base text-gray-900 leading-tight mb-2">'.$item['name'].'</h4>
                        <!-- التصنيف هنا -->
                        <p class="text-xs category-text font-medium mb-2" style="color: #C8A95A !important;">التصنيف: '.$category_name.'</p>
                    </div>
                    <div class="flex items-end gap-2 mt-auto">
                        <span class="font-bold text-base text-gray-900">'.$item['price'].' د.م</span>
                    </div>
                </div>
            </div>';
        }
        echo '</div>';
    } catch (Exception $e) { echo "خطأ في جلب البيانات"; }
}
?>