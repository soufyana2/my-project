<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // التصحيح هنا: نخرج مستوى واحد فقط من wordpress إلى my-project
    $root = dirname(__DIR__); 

    // التأكد من اسم الملف، إذا كان اسمه .env نستخدم الحالة الأولى
    if (file_exists($root . '/.env')) {
        $dotenv = Dotenv::createImmutable($root);
        $dotenv->load();
    } 
    // إذا كان اسمه apikeys.env نستخدم هذه الحالة
    elseif (file_exists($root . '/apikeys.env')) {
        $dotenv = Dotenv::createImmutable($root, 'apikeys.env');
        $dotenv->load();
    }
} catch (Exception $e) {
    // خطأ في تحميل ملف البيئة
}
if (!isset($_SESSION['user_id'])) { exit; }

$woocommerce = new Automattic\WooCommerce\Client(
    $_ENV['wordpress_url'], $_ENV['consumer_key'], $_ENV['secret_key'],
    ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 30]
);

$stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($ids)) {
    echo '<div class="text-center py-20">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252544/empty_wishlist_ciqog9.png" class="w-20 mx-auto  mb-4 empty-menu-icon">
            <h3 class="text-gray-500 font-bold" style="font-family:\'Cairo\';">مفضلتك فارغة</h3>
          </div>';
} else {
    try {
        $products = $woocommerce->get('products', ['include' => array_map('intval', $ids), 'per_page' => 100]);
        $products = json_decode(json_encode($products), true);
        $_SESSION['wishlist_cache'] = $products;

echo '<div class="text-xs font-bold mb-4 text-right" style="font-size: 14px; font-family: \'Cairo\';">لديك <span id="wishlist-sidebar-count-text">'.count($products).'</span> عناصر.</div>';        echo '<div class="space-y-4">';
        foreach ($products as $item) {
            $cat = !empty($item['categories']) ? $item['categories'][0]['name'] : 'منتج';
            $img = !empty($item['images']) ? $item['images'][0]['src'] : '';
            ?>
            <!-- كرت المنتج الاحترافي الموحد -->
            <div class="wishlist-item-row product-card-professional group p-4 flex flex-row items-start gap-4 bg-white border border-gray-100 rounded-lg relative" data-id="<?php echo $item['id']; ?>">
                <div class="w-24 h-32 flex-shrink-0 overflow-hidden relative rounded-lg border border-gray-100">
                    <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?php echo $img; ?>');"></div>
                    <!-- زر الحذف الموحد -->
                    <button onclick="toggleWishlist(this, event)" data-product-id="<?php echo $item['id']; ?>" class="remove-product-icon active absolute top-1.5 left-1.5 w-7 h-7 bg-white/90 shadow-sm text-black rounded-full flex items-center justify-center transition-all">
                        <i class="ph ph-trash text-base"></i>
                    </button>
                </div>
                <div class="flex flex-col flex-grow min-w-0 text-right">
                    <h4 class="font-bold text-sm text-gray-900 leading-tight mb-1"><?php echo $item['name']; ?></h4>
                    <p class="category-text text-xs font-semibold mb-2" style="color: #C8A95A !important;">التصنيف: <?php echo $cat; ?></p>
                    <div class="mt-auto">
                        <span class="font-bold text-base text-gray-900"><?php echo $item['price']; ?> د.م</span>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } catch (Exception $e) { echo 'خطأ في جلب البيانات'; }
}