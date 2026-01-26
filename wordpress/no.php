حلل كامل هاد ملفات لارسلت لك ، وحللها جيدا وبتفصيل الممل اريد كل نقطة تتكلم عليها ،بعدها سوي لرح اقول:

اولا : اريدك تتصرف كمخترق،وتشوف التغرات في كل نظام وملف، وتقول لي خطوة خطوة تحليل شامل عن الأمان لكل ملف ، ووش المخاطر

تانيا: اريدك تحلل كل الملفات و تقول لي كل ملف ونسبة امانه في المئة ٪ ونسبة سرعته في ٪ أيضا ، ونسبة تجربة مستخدم جيدة في المئة أيضا ٪ وكل ملف كم جيد seoفي المئة.

تالتا: اريدك كل ملف فيه كاتش، تقول لي ماهو توقيت حدف الكاتش فيه(ولو مو موجود قل لي لأنها نقطة مهمة)

رابعا: بما أنني في مرحلة رفع نظام لسيرفر،اريدك تقول لي بتفصيل كل تغييرات للازم أسويها ملف ملف حتي يشتغل نظامي بدون مشاكل علي سيرفر.

خامسا: اريدك تقول لي هل هناك ملف فيه apikeysاو رابط معين خلص بووكوميرس لازم يكون امن موجود في javascript

سادسا: هناك مشكل في نظام تدكرني ،لما اطلع وارجع مايشتغل لازم اسجل من جديد،شف له حل او قل لي كيف اخليه ابسط واسوي تعليق للمعقد حتي احلو لاحقا.

سابعا: اريدك تقول لي بتفصيل صفحات للازم اسوي include لملف headers secure فيها

تامنا: اريدك تقول لي لو حطيت اماكن apikeys and keys .envخارج مجلدي هل رح تعمل عادي ؟

تاسعا:اريدك تقول لي هل ساحتاح تغيير مسارات ملفاتي في الكود لما اضيف نظام لسيرفر .

عاشرا: اريدك تقول هل ساحتاح robot.txt and sitemap ، ولو نعم سويهم لي واشرحهم بتفصيل .

11اريدك تقول لي هل ملف cleaning.phpيشمل تنظيف كل نظام أو هناك نواقص يجب انظفها حتي يكون شامل ولو نعم قل لي.

12 اريدك في الاخير تقيم لي كامل نظام وتقول لي كم هو كامل في ٪ ، وهل جيد وجاهز للبيع، وهل غالبا سيعمل لفترة بدون مشاكل ، وهل زابون رح يعجبو، وقل لي هل مشكلة spinnerفي سلة ومفضلة رح تبين فرق وهل تقنيات جيدة، وهل لما ارفعو لسيرفر بيكون اخف<?php
/**

Cron Job Cleaning Script

Safe, Professional, and Customized.
*/

// 1. الأمان: منع التشغيل من المتصفح (لن يظهر في متجرك أبداً)
if (php_sapi_name() !== 'cli') {
http_response_code(403);
die('Forbidden: CLI access only.');
}

// 2. تضمين الملفات المطلوبة
require_once 'vendor/autoload.php';
require_once 'logger_setup.php';
require_once 'db.php'; // تم إضافته كما طلبت (تأكد أن المتغير داخله اسمه $pdo)

// إعدادات المدة الزمنية (بالأيام)
$logs_retention_days = 25;      // الاحتفاظ بالسجلات لمدة 25 يوم
$attempts_retention_days = 2;   // الاحتفاظ بالمحاولات لمدة يومين فقط (للحفاظ على سرعة الموقع)
$general_cleanup_days = 15;     // تنظيفات عامة أخرى (مثل التوكنات المنتهية)

$cronLogger = getLogger('cron');
$cronLogger->info('--- Starting Cleanup Process ---');
$startTime = microtime(true);

try {
if (!isset(
pdo) not found in db.php");
}

code
Code
download
content_copy
expand_less
// حساب التواريخ بالثواني (Unix Timestamp)
$time_for_logs = time() - ($logs_retention_days * 24 * 60 * 60);
$time_for_attempts = time() - ($attempts_retention_days * 24 * 60 * 60);
// للتاريخ بصيغة MySQL (Y-m-d H:i:s)
$date_for_general = date('Y-m-d H:i:s', time() - ($general_cleanup_days * 24 * 60 * 60));

// =============================================
// 1. تنظيف السجلات (Logs) - (25 يوم)
// =============================================

// تنظيف سجلات OTP
$stmt = $pdo->prepare("DELETE FROM otp_logs WHERE created_at < ?");
$stmt->execute([$time_for_logs]);
$deletedOtpLogs = $stmt->rowCount();

// تنظيف سجلات التسجيل
$stmt = $pdo->prepare("DELETE FROM registration_logs WHERE created_at < ?");
$stmt->execute([$time_for_logs]);
$deletedRegLogs = $stmt->rowCount();

// (NEW) تنظيف سجلات الدخول login_logs
// أفترض أن الجدول يحتوي حقل created_at أو login_time
// إذا كان الحقل timestamp (رقم) استخدم $time_for_logs
// إذا كان الحقل datetime (تاريخ) استخدم date(...)
// سأستخدم الصيغة الرقمية المتوافقة مع باقي جداولك:
$stmt = $pdo->prepare("DELETE FROM login_logs WHERE created_at < ?"); 
$stmt->execute([$time_for_logs]);
$deletedLoginLogs = $stmt->rowCount();

if ($deletedOtpLogs > 0 || $deletedRegLogs > 0 || $deletedLoginLogs > 0) {
    $cronLogger->info("Cleaned Logs (Older than $logs_retention_days days).", [
        'otp_logs' => $deletedOtpLogs,
        'reg_logs' => $deletedRegLogs,
        'login_logs' => $deletedLoginLogs
    ]);
}

// =============================================
// 2. تنظيف المحاولات (Attempts) - (يومين لسرعة الموقع)
// =============================================

// login_attempts
$stmt = $pdo->prepare("DELETE FROM login_attempts WHERE updated_at < ?");
$stmt->execute([$time_for_attempts]);

// registration_attempts
$stmt = $pdo->prepare("DELETE FROM registration_attempts WHERE updated_at < ?");
$stmt->execute([$time_for_attempts]);

// otp_attemptsssss
$stmt = $pdo->prepare("DELETE FROM otp_attemptsssss WHERE updated_at < ?");
$stmt->execute([$time_for_attempts]);

// password_reset_attempts
$stmt = $pdo->prepare("DELETE FROM password_reset_attempts WHERE last_attempt < ?");
$stmt->execute([$time_for_attempts]);

// ip_attemptss (عدادات الحظر اليومي)
$stmt = $pdo->prepare("DELETE FROM ip_attemptss WHERE last_attempt < ?");
$stmt->execute([$time_for_attempts]);

$cronLogger->info("Cleaned Rate Limit Tables (Older than $attempts_retention_days days).");


// =============================================
// 3. تنظيف البيانات المنتهية الصلاحية (Expired Data)
// =============================================

// Remember Tokens (المنتهية فعلياً)
$stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
$stmt->execute();

// Password Resets Tokens (المنتهية فعلياً)
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
$stmt->execute();

// Blocked IPs (التي انتهى وقت حظرها)
$stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE expiry < NOW()");
$stmt->execute();


// =============================================
// النهاية
// =============================================
$duration = round(microtime(true) - $startTime, 4);
$cronLogger->info("Cleanup Job Finished.", ['duration' => $duration]);

// رسالة لمدير السيرفر (تصل للإيميل)
echo "Success: Database cleanup completed in $duration seconds.";

} catch (Exception $e) {
// تسجيل الخطأ
$cronLogger->critical("Cleanup Failed.", ['error' => $e->getMessage()]);
// إرسال الخطأ للإيميل
echo "Error: " . $e->getMessage();
}<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

𝑖
𝑠
𝐿
𝑜
𝑔
𝑔
𝑒
𝑑
𝐼
𝑛
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
isLoggedIn=isset(
_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

$action = $_POST['action'] ?? '';

𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑖
𝑑
=
𝑖
𝑛
𝑡
𝑣
𝑎
𝑙
(
product
i
	​

d=intval(
_POST['product_id'] ?? 0);

𝑣
𝑎
𝑟
𝑖
𝑎
𝑡
𝑖
𝑜
𝑛
𝑖
𝑑
=
𝑖
𝑛
𝑡
𝑣
𝑎
𝑙
(
variation
i
	​

d=intval(
_POST['variation_id'] ?? 0);

𝑞
𝑢
𝑎
𝑛
𝑡
𝑖
𝑡
𝑦
=
𝑖
𝑛
𝑡
𝑣
𝑎
𝑙
(
quantity=intval(
_POST['quantity'] ?? 1);
$size = $_POST['size'] ?? null;
$color = $_POST['color'] ?? null;

// مفتاح فريد للزوار لتمييز المنتجات ذات المقاسات المختلفة
$cart_key = $product_id . '' . $variation_id . '' . $size . '_' . $color;

if (
isLoggedIn) {
// منطق المسجلين (Database)
$stmt = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ? AND variation_id = ? AND selected_size = ? AND selected_color = ?");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
user_id, $product_id, $variation_id, $size, $color]);
$existing = $stmt->fetch();

code
Code
download
content_copy
expand_less
if ($existing) {
        $new_qty = $existing['quantity'] + $quantity;
        $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ?")->execute([$new_qty, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO user_cart (user_id, product_id, variation_id, quantity, selected_size, selected_color) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$user_id, $product_id, $variation_id, $quantity, $size, $color]);
    }
} else {
    // منطق الزوار (Session)
    if (!isset($_SESSION['guest_cart'])) { $_SESSION['guest_cart'] = []; }
    if (isset($_SESSION['guest_cart'][$cart_key])) {
        $_SESSION['guest_cart'][$cart_key]['quantity'] += $quantity;
    } else {
        $_SESSION['guest_cart'][$cart_key] = [
            'id' => $cart_key,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $quantity,
            'selected_size' => $size,
            'selected_color' => $color
        ];
    }
}
echo json_encode(['status' => 'success']);

}
elseif ($action == 'remove') {
$cart_id = 
𝑃
𝑂
𝑆
𝑇
[
′
𝑐
𝑎
𝑟
𝑡
𝑖
𝑑
′
]
;
/
/
فيحالةالزائرهو
𝑐
𝑎
𝑟
𝑡
𝑘
𝑒
𝑦
𝑖
𝑓
(
P
	​

OST[
′
cart
i
	​

d
′
];//فيحالةالزائرهوcart
k
	​

eyif(
isLoggedIn) {

𝑝
𝑑
𝑜
−
>
𝑝
𝑟
𝑒
𝑝
𝑎
𝑟
𝑒
(
"
𝐷
𝐸
𝐿
𝐸
𝑇
𝐸
𝐹
𝑅
𝑂
𝑀
𝑢
𝑠
𝑒
𝑟
𝑐
𝑎
𝑟
𝑡
𝑊
𝐻
𝐸
𝑅
𝐸
𝑖
𝑑
=
?
𝐴
𝑁
𝐷
𝑢
𝑠
𝑒
𝑟
𝑖
𝑑
=
?
"
)
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
pdo−>prepare("DELETEFROMuser
c
	​

artWHEREid=?ANDuser
i
	​

d=?")−>execute([
cart_id, 
_SESSION['guest_cart'][
action == 'update_qty') {
$cart_id = $_POST['cart_id'];

𝑛
𝑒
𝑤
𝑞
𝑡
𝑦
=
𝑚
𝑎
𝑥
(
1
,
𝑖
𝑛
𝑡
𝑣
𝑎
𝑙
(
new
q
	​

ty=max(1,intval(
_POST['new_qty']));
if ($isLoggedIn) {

𝑝
𝑑
𝑜
−
>
𝑝
𝑟
𝑒
𝑝
𝑎
𝑟
𝑒
(
"
𝑈
𝑃
𝐷
𝐴
𝑇
𝐸
𝑢
𝑠
𝑒
𝑟
𝑐
𝑎
𝑟
𝑡
𝑆
𝐸
𝑇
𝑞
𝑢
𝑎
𝑛
𝑡
𝑖
𝑡
𝑦
=
?
𝑊
𝐻
𝐸
𝑅
𝐸
𝑖
𝑑
=
?
𝐴
𝑁
𝐷
𝑢
𝑠
𝑒
𝑟
𝑖
𝑑
=
?
"
)
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
pdo−>prepare("UPDATEuser
c
	​

artSETquantity=?WHEREid=?ANDuser
i
	​

d=?")−>execute([
new_qty, $cart_id, 
_SESSION['guest_cart'][$cart_id])) {

𝑆
𝐸
𝑆
𝑆
𝐼
𝑂
𝑁
[
′
𝑔
𝑢
𝑒
𝑠
𝑡
𝑐
𝑎
𝑟
𝑡
′
]
[
S
	​

ESSION[
′
guest
c
	​

art
′
][
cart_id]['quantity'] = $new_qty;
}
}
echo json_encode(['status' => 'success']);
}<?php
// filter_ajax.php
require_once 'logger_setup.php';
ini_set('display_errors', 0);
session_start();
// --- أضف هذا الجزء هنا ---
require_once 'db.php';

𝑢
𝑠
𝑒
𝑟
𝑤
𝑖
𝑠
ℎ
𝑙
𝑖
𝑠
𝑡
𝑖
𝑑
𝑠
=
[
]
;
𝑖
𝑓
(
𝑖
𝑠
𝑠
𝑒
𝑡
(
user
w
	​

ishlist
i
	​

ds=[];if(isset(
_SESSION['user_id'])) {
$stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ?");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
_SESSION['user_id']]);
$user_wishlist_ids = 
_SERVER['REQUEST_METHOD'] !== 'POST') exit(json_encode(['error' => 'Invalid Request']));

require DIR . '/vendor/autoload.php';
use Dotenv\Dotenv;
use Automattic\WooCommerce\Client;

try {
$dotenv = Dotenv::createImmutable(DIR, 'apikeys.env');
$dotenv->load();
} catch (Exception $e) { exit(); }

$woocommerce = new Client(
$_ENV['wordpress_url'],
$_ENV['consumer_key'],
$_ENV['secret_key'],
['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 40]
);

// ==========================================
// 1. خرائط الترجمة (Matching Maps)
// هنا نقوم بربط الـ Slug الإنجليزي بالاسم العربي في متجرك
// ==========================================

// خريطة الألوان: (القيمة من HTML => القيمة في المتجر)
$color_map = [
'red'    => 'أحمر',
'black'  => 'أسود',
'white'  => 'أبيض',
'blue'   => 'أزرق',
'green'  => 'أخضر',
'gold'   => 'ذهبي',
'silver' => 'فضي',
'beige'  => 'بيج',
'brown'  => 'بني',
'yellow' => 'أصفر',
'grey'   => 'رمادي',
'purple' => 'بنفسجي',
'orange' => 'برتقالي',
'navy'   => 'كحلي',
'turquoise' => 'تركوازي'
];

// خريطة الأحجام (مل): (القيمة من HTML => القيمة في المتجر)
// ملاحظة: تأكد هل تكتبها "30 مل" أم "30ml" في المنتجات. وضعت الاحتمالين
$volume_map = [
'30ml'  => ['30 مل', '30ml', '30ML'],
'50ml'  => ['50 مل', '50ml', '50ML'],
'75ml'  => ['75 مل', '75ml', '75ML'],
'100ml' => ['100 مل', '100ml', '100ML'],
'150ml' => ['150 مل', '150ml', '150ML'],
'200ml' => ['200 مل', '200ml', '200ML'],
];

// ==========================================

𝑝
𝑎
𝑔
𝑒
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
page=isset(
_POST['page']) ? intval($_POST['page']) : 1;
// --- التحديث المطلوب (Update) ---
// استبدل السطر القديم بهذا السطر الآمن الذي يعمل بدون وردبريس

𝑠
𝑒
𝑎
𝑟
𝑐
ℎ
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
search=isset(
_POST['search']) ? htmlspecialchars(strip_tags(trim($_POST['search'])), ENT_QUOTES, 'UTF-8') : '';
$per_page = 10;

$params = [
'status' => 'publish',
'page' => $page,
'per_page' => $per_page,
'order' => 'desc',
'orderby' => 'date'
];

// --- أضف هذا الجزء هنا ليفعل البحث الذكي ---
// --- منطق البحث الشامل والمصحح (تحديث) ---
if (!empty($search)) {
try {
// البحث عن الوسوم أولاً (لدعم كلمات مثل 3orod)
$tags_found = $woocommerce->get('products/tags', ['search' => $search]);

code
Code
download
content_copy
expand_less
if (!empty($tags_found)) {
        $tag_ids = [];
        foreach($tags_found as $t) { $tag_ids[] = $t->id; }
        $params['tag'] = implode(',', $tag_ids);
    } else {
        // إذا لم يجد وسماً، يبحث في الاسم
        $params['search'] = $search;
    }
} catch (Exception $e) {
    $params['search'] = $search;
}
// ملاحظة: عند البحث، نتجاهل التصنيفات المختارة لضمان ظهور النتائج من أي قسم

} else {
// لا يتم تفعيل فلترة التصنيفات إلا إذا كان حقل البحث فارغاً
if (isset(
_POST['categories'])) {

𝑐
𝑎
𝑡
𝑠
=
𝑎
𝑟
𝑟
𝑎
𝑦
𝑢
𝑛
𝑖
𝑞
𝑢
𝑒
(
𝑎
𝑟
𝑟
𝑎
𝑦
𝑓
𝑖
𝑙
𝑡
𝑒
𝑟
(
cats=array
u
	​

nique(array
f
	​

ilter(
_POST['categories'], function($v) { return 
cats)) {
$params['category'] = implode(',', 
_POST['categories']) && is_array($_POST['categories'])) {

𝑐
𝑎
𝑡
𝑠
=
𝑎
𝑟
𝑟
𝑎
𝑦
𝑢
𝑛
𝑖
𝑞
𝑢
𝑒
(
𝑎
𝑟
𝑟
𝑎
𝑦
𝑓
𝑖
𝑙
𝑡
𝑒
𝑟
(
cats=array
u
	​

nique(array
f
	​

ilter(
_POST['categories'], function($v) { return 
cats)) {
$params['category'] = implode(',', $cats);
}
}

if (isset(
_POST['max_price'])) {
$params['max_price'] = $_POST['max_price'];
$params['min_price'] = '0';
}

𝑠
𝑒
𝑙
𝑒
𝑐
𝑡
𝑒
𝑑
𝑠
𝑖
𝑧
𝑒
𝑠
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
selected
s
	​

izes=isset(
_POST['sizes']) ? array_unique($_POST['sizes']) : [];

𝑠
𝑒
𝑙
𝑒
𝑐
𝑡
𝑒
𝑑
𝑐
𝑜
𝑙
𝑜
𝑟
𝑠
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
selected
c
	​

olors=isset(
_POST['colors']) ? array_unique($_POST['colors']) : [];

𝑠
𝑒
𝑙
𝑒
𝑐
𝑡
𝑒
𝑑
𝑣
𝑜
𝑙
𝑢
𝑚
𝑒
𝑠
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
selected
v
	​

olumes=isset(
_POST['volumes']) ? array_unique($_POST['volumes']) : [];

try {
// تحديث: إضافة متغير $search لضمان عدم تداخل نتائج البحث في الكاش

params) . 
𝑠
𝑒
𝑎
𝑟
𝑐
ℎ
.
𝑗
𝑠
𝑜
𝑛
𝑒
𝑛
𝑐
𝑜
𝑑
𝑒
(
search.json
e
	​

ncode(
selected_sizes) . json_encode(
𝑠
𝑒
𝑙
𝑒
𝑐
𝑡
𝑒
𝑑
𝑐
𝑜
𝑙
𝑜
𝑟
𝑠
)
.
𝑗
𝑠
𝑜
𝑛
𝑒
𝑛
𝑐
𝑜
𝑑
𝑒
(
selected
c
	​

olors).json
e
	​

ncode(
selected_volumes));    $cacheFile = DIR . '/cache/' . $cacheKey . '.json';

code
Code
download
content_copy
expand_less
// --- منطق العدد الكلي (Total Store Count) ---
$totalStoreFile = __DIR__ . '/cache/store_total_count.txt';
$total_store_products = 0;

if (file_exists($totalStoreFile) && (time() - filemtime($totalStoreFile) < 3600)) {
    $total_store_products = (int)file_get_contents($totalStoreFile);
} else {
    // محاولة جلب العدد
    $woocommerce->get('products', ['status' => 'publish', 'per_page' => 1]);
    $headers = $woocommerce->http->getResponse()->getHeaders();
    
    // إصلاح مشكلة الصفر: التحقق من الهيدر بجميع حالات الأحرف
    if (isset($headers['x-wp-total'])) {
        $total_store_products = (int)$headers['x-wp-total'];
    } elseif (isset($headers['X-WP-Total'])) {
        $total_store_products = (int)$headers['X-WP-Total'];
    } else {
        // حل بديل أخير إذا السيرفر يحجب الهيدر: جلب تقرير بسيط
        // (هذه الخطوة قد تكون ثقيلة قليلاً لكنها تضمن الرقم)
        try {
            $reports = $woocommerce->get('reports/products/totals');
            foreach($reports as $rep) {
                if($rep->slug == 'publish') $total_store_products = $rep->total;
            }
        } catch(Exception $ex) { $total_store_products = 0; }
    }

    if(!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);
    file_put_contents($totalStoreFile, $total_store_products);
}

$products = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 600)) {
    $cachedData = json_decode(file_get_contents($cacheFile), true);
    $products = $cachedData['products'];
} else {
    $products = $woocommerce->get('products', $params);
    
    // ==========================================
    // منطق الفلترة المتقدم (مع الترجمة)
    // ==========================================
    if ((!empty($selected_sizes) || !empty($selected_colors) || !empty($selected_volumes)) && !empty($products)) {
        $products = array_filter($products, function($p) use ($selected_sizes, $selected_colors, $selected_volumes, $color_map, $volume_map) {
            
            $pass_size = empty($selected_sizes);
            $pass_color = empty($selected_colors);
            $pass_volume = empty($selected_volumes);

            // تجهيز قيم البحث المترجمة
            $search_colors = [];
            foreach($selected_colors as $sc) {
                $search_colors[] = $sc; // القيمة الأصلية (مثلا red)
                if(isset($color_map[$sc])) $search_colors[] = $color_map[$sc]; // القيمة العربية (أحمر)
            }

          $search_volumes = [];

foreach($selected_volumes as 
volume_map[
volume_map[$sv] as $v) {

v));
}
} else {

sv));
}
}

code
Code
download
content_copy
expand_less
// التكرار داخل سمات المنتج
            foreach ($p->attributes as $attr) {
                // توحيد الاسم والـ slug للمقارنة (إزالة الرموز)
                $slug_raw = urldecode($attr->slug);
                $name_raw = $attr->name;
                
                // 1. فحص الألوان
                // نبحث عن أي سمة اسمها "اللون" أو "color" أو "pa_color"
                if (!empty($selected_colors)) {

if (in_array(
𝑛
𝑎
𝑚
𝑒
𝑟
𝑎
𝑤
,
[
′
اللو
ن
′
,
′
الألوا
ن
′
,
′
ألوا
ن
′
]
)
∣
∣
𝑖
𝑛
𝑎
𝑟
𝑟
𝑎
𝑦
(
name
r
	​

aw,[
′
اللون
′
,
′
الألوان
′
,
′
ألوان
′
])∣∣in
a
	​

rray(
slug_raw, ['اللون', 'الألوان', 'ألوان', 'pa_color'])) {
if(!empty(array_intersect($search_colors, $attr->options))) {
$pass_color = true;
}
}
}

code
Code
download
content_copy
expand_less
// 2. فحص المقاسات (Sizes)
                // نبحث عن "المقاس" أو "size" أو "pa_size"
                if (!empty($selected_sizes)) {
                    if ($slug_raw == 'المقاس' || $slug_raw == 'pa_size' || $name_raw == 'المقاس') {
                        // في المقاسات عادة لا نحتاج ترجمة (S, M, L, XL) تطابق ما في المتجر
                        // نحول القيمتين لـ UpperCase للتأكد (s => S)
                        $options_upper = array_map('strtoupper', $attr->options);
                        $selected_upper = array_map('strtoupper', $selected_sizes);
                        
                        if(!empty(array_intersect($selected_upper, $options_upper))) {
                            $pass_size = true;
                        }
                    }
                }

                // 3. فحص الأحجام (Volumes - ml)
                // طلبت أن تكون داخل سمة "المقاس" أيضاً
              // 3. فحص الأحجام (Volumes - ml)

if (!empty(
slug_raw, 'size') !== false || strpos(
𝑛
𝑎
𝑚
𝑒
𝑟
𝑎
𝑤
,
′
المقا
س
′
)
!
=
=
𝑓
𝑎
𝑙
𝑠
𝑒
∣
∣
𝑠
𝑡
𝑟
𝑝
𝑜
𝑠
(
name
r
	​

aw,
′
المقاس
′
)!==false∣∣strpos(
name_raw, 'الحجم') !== false) {

code
Code
download
content_copy
expand_less
// تنظيف الخيارات القادمة من المتجر (حذف المسافات وتحويلها لصغير) قبل المقارنة
    $cleaned_product_options = array_map(function($opt) {
        return str_replace(' ', '', strtolower($opt));
    }, $attr->options);

    // الآن نقارن القيم المنظفة ببعضها
    if(!empty(array_intersect($search_volumes, $cleaned_product_options))) {
        $pass_volume = true;
    }
}

}
}

code
Code
download
content_copy
expand_less
return $pass_size && $pass_color && $pass_volume;
        });
        $products = array_values($products);
    }
    
    if(!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);
    file_put_contents($cacheFile, json_encode(['products' => $products]));
}

} catch (Exception $e) {
$products = [];
$total_store_products = 0;
}

function renderCardHTML($product) {
global $user_wishlist_ids; // لجلب المصفوفة من الخارج

code
Code
download
content_copy
expand_less
if(is_object($product)) $product = json_decode(json_encode($product), true);

$image_src = !empty($product['images'][0]['src']) ? $product['images'][0]['src'] : "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=";
$hover_image_src = (isset($product['images'][1]) && !empty($product['images'][1]['src'])) ? $product['images'][1]['src'] : $image_src;
$category_name = !empty($product['categories'][0]['name']) ? $product['categories'][0]['name'] : "غير مصنف";
$product_title = $product['name'];
$price = $product['price'] ? number_format($product['price'], 2) . ' د.م' : '';
$regular_price = $product['regular_price'] && ($product['regular_price'] > $product['price']) ? number_format($product['regular_price'], 2) . ' د.م' : '';
$product_link = 'product.php?id=' . $product['id'];
$is_active = in_array($product['id'], $user_wishlist_ids) ? 'active' : '';

ob_start();
?>
<div class="product-card cursor-pointer group card-load-animation fade-in-up" style="--card-bg-color: var(--card-one-bg);">
    <div class="relative flex-grow flex flex-col">
        <a href="<?php echo $product_link; ?>" class="block">
            <div class="image-container skeleton-pending" 
                 data-main-image-src="<?php echo $image_src; ?>" 
                 data-hover-image-src="<?php echo $hover_image_src; ?>">
                <img loading="lazy" src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product_title); ?>" class="main-product-image">
                <img loading="lazy" src="<?php echo $hover_image_src; ?>" alt="<?php echo htmlspecialchars($product_title); ?> Hover" class="hover-product-image">
                
                  <!-- تحديث الزر هنا: أضفنا data-product-id والكلاس النشط -->
                <button class="wishlist-icon <?php echo $is_active; ?>" 
                        data-product-id="<?php echo $product['id']; ?>" 
                        onclick="toggleWishlist(this, event)">
                    <i class="fa-regular fa-heart icon-empty"></i>
                    <i class="fa-solid fa-heart icon-filled"></i>
                </button>
            </div>
        </a>
        <div class="mt-auto w-full info-part flex flex-col items-start text-right">
            <p class="text-xs font-bold underline category-gold-beige arabic-font"><?php echo htmlspecialchars($category_name); ?></p>
            <h3 class="product-title text-sm font-bold mt-1 arabic-font text-gray-800"><?php echo htmlspecialchars($product_title); ?></h3>
            <div class="product-price-small flex gap-1 items-center">
                <p class="text-sm font-bold arabic-font" style="color: black !important;"><?php echo $price; ?></p>
                <?php if ($regular_price): ?>
                    <p class="text-xs text-gray-400 line-through arabic-font"><?php echo $regular_price; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
return ob_get_clean();

}

ℎ
𝑡
𝑚
𝑙
=
′
′
;
𝑖
𝑓
(
!
𝑒
𝑚
𝑝
𝑡
𝑦
(
html=
′′
;if(!empty(
products)) {
foreach ($products as $product) {

ℎ
𝑡
𝑚
𝑙
.
=
𝑟
𝑒
𝑛
𝑑
𝑒
𝑟
𝐶
𝑎
𝑟
𝑑
𝐻
𝑇
𝑀
𝐿
(
html.=renderCardHTML(
product);
}
}

ℎ
𝑎
𝑠
𝑚
𝑜
𝑟
𝑒
=
(
𝑐
𝑜
𝑢
𝑛
𝑡
(
has
m
	​

ore=(count(
products) >= $per_page);

echo json_encode([
'html' => $html,
'has_more' => $has_more,
'total_store_products' => $total_store_products
]);
?><?php
require_once 'db.php';
require_once 'functions.php';

// فحص حالة الاشتراك المسبق للـ IP
if(isset(
_SESSION['has_subscribed'])){
try {
$ip_check = getClientIP();
$stmt = $pdo->prepare("SELECT id FROM subscribers WHERE ip_address = ? LIMIT 1");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
ip_check]);
if($stmt->rowCount() > 0) {
$_SESSION['has_subscribed'] = true;
}
} catch (Exception $e) {}
}

// ضمان وجود توكن CSRF عند تحميل الصفحة
if (!isset($_SESSION['csrf_token'])) {
$csrf_token = generate_csrf_token();
} else {
$csrf_token = $_SESSION['csrf_token'];
}
?>

<style>
    /* --- تم الحفاظ على الستايلات الخاصة بك 100% --- */
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap');
    .text-gold { color: #C8A95A; }
    .border-gold { border-color: #C8A95A; }
    .bg-black-footer { background-color: #000000; }
    .font-cairo { font-family: 'Cairo', sans-serif; }
    .font-playfair { font-family: 'Playfair Display', serif; }
    .footer-link { color: #d1d5db; transition: all 0.3s ease; display: inline-block; text-decoration: none; }
    @media (min-width: 1024px) {
        .footer-link:hover { color: #C8A95A; transform: translateX(-5px); }
    }
    .footer-input { background-color: transparent; border: none; border-bottom: 1px solid #4b5563; color: white; width: 100%; max-width: 300px; padding: 10px 0; outline: none; transition: border-color 0.3s ease; text-align: center; }
    .footer-input:focus { border-color: #C8A95A; }
    .footer-input::placeholder { color: #6b7280; }
    @media (min-width: 768px) { .footer-input { text-align: right; } }
    .btn-footer { font-family: 'Cairo', sans-serif; background-color: transparent; border: 1px solid #ffffff; color: #ffffff; padding: 0.6rem 1.5rem; font-weight: bold; transition: all 0.3s ease; cursor: pointer; margin-top: 1rem; font-size: 0.9rem; }
    @media (hover: hover) and (min-width: 1024px) { .btn-footer:hover { border-color: #C8A95A; color: #C8A95A; background-color: rgba(200, 169, 90, 0.1); } }
    .studio-link { color: #C8A95A; text-decoration: none; font-weight: bold; transition: all 0.3s ease; }
    @media (min-width: 1024px) { .studio-link:hover { text-decoration: underline; } }
</style>

<footer class="bg-black-footer text-white pt-16 pb-8 border-t border-gray-900" dir="rtl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12 text-center md:text-right">

code
Code
download
content_copy
expand_less
<div class="flex flex-col items-center md:items-start space-y-4">
            <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">نبذة عنا</h4>
            <p class="font-cairo text-gray-400 text-sm leading-relaxed max-w-xs mx-auto md:mx-0">
                نقدم أرقى العُطُور والإكسسوارات الفاخرة التي تعكس ذوقك الرفيع. الجودة والأصالة هما عنواننا الدائم.
            </p>
        </div>

        <div class="flex flex-col items-center md:items-start space-y-4">
            <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">روابط سريعة</h4>
            <ul class="space-y-2 font-cairo text-sm w-full">
                <li><a href="/my-project/wordpress/index.php?categurie=index" class="footer-link">الرئيسية</a></li>
                <li><a href="/my-project/wordpress/filter.php?categurie=filter" class="footer-link">منتجاتنا</a></li>
            </ul>
        </div>

        <div class="flex flex-col items-center md:items-start space-y-4">
            <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">المساعدة والسياسات</h4>
            <ul class="space-y-2 font-cairo text-sm w-full">
                <li><a href="/my-project/wordpress/contact/privacy.php" class="footer-link">سياسة الخصوصية</a></li>
                <li><a href="/my-project/wordpress/contact/contact.php" class="footer-link">اتصل بنا</a></li>
            </ul>
        </div>

        <div class="flex flex-col items-center md:items-start space-y-4">
            <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">النشرة البريدية</h4>
            <p class="font-cairo text-gray-400 text-sm max-w-xs mx-auto md:mx-0">اشترك الآن للحصول على آخر العروض والأخبار الحصرية.</p>
   <!-- الجزء الخاص بالفورم في الفوتر -->

<form id="subscribeForm" class="flex flex-col items-center md:items-start w-full">
    <input type="hidden" name="csrf_token" id="footer_csrf" value="<?php echo $csrf_token; ?>">
    <div style="display: none;"><input type="text" name="website_trap"></div>

code
Code
download
content_copy
expand_less
<input type="email" id="sub_email" name="email" placeholder="أدخل بريدك الإلكتروني" 
       class="footer-input font-cairo mb-2" required>

<button type="submit" id="sub_btn" class="btn-footer w-full md:w-auto">
    <span id="btnText">اشترك</span>
</button>
<div id="sub_msg" class="text-sm mt-2 font-cairo"></div>
</form>

code
Code
download
content_copy
expand_less
</div>
    </div>

    <div class="border-t border-gray-900 w-full mb-8"></div>
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-center">
        <p class="font-cairo text-gray-500 text-sm order-2 md:order-1 text-right">إكسسوارات عبدالوهاب © جميع الحقوق محفوظة</p>
        <div class="font-playfair text-white text-sm order-1 md:order-2" dir="ltr">
            Designed by : <a href="https://www.primestore.ma" class="studio-link">Primestore.ma</a>
        </div>
    </div>
</div>
</footer>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<script>
document.getElementById('subscribeForm')?.addEventListener('submit', function(e) {
    e.preventDefault(); // منع تحديث الصفحة تماماً
    
    const btn = document.getElementById('sub_btn');
    const btnText = document.getElementById('btnText');
    const msgDiv = document.getElementById('sub_msg');
    const csrfInput = document.getElementById('footer_csrf');
    const emailInput = document.getElementById('sub_email');
    const form = e.target;

    // تعطيل الزر ومنع التكرار
    btn.disabled = true;
    btnText.innerText = 'جاري المعالجة...';
    msgDiv.innerText = '';

    const formData = new FormData(form);

    fetch('subscribe_process.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        // تحديث التوكن للعملية القادمة
        if (data.new_token) {
            csrfInput.value = data.new_token;
        }

        if (data.status === 'success') {
            msgDiv.style.color = '#C8A95A';
            msgDiv.innerText = data.message;
            emailInput.value = ''; 
        } else {
            msgDiv.style.color = '#ef4444';
            msgDiv.innerText = data.message;
        }
    })
    .catch(error => {
        msgDiv.style.color = '#ef4444';
        msgDiv.innerText = 'حدث خطأ في الاتصال، حاول مجدداً.';
    })
    .finally(() => {
        // إعادة الزر لحالته الطبيعية
        btn.disabled = false;
        btnText.innerText = 'اشترك';
    });
});
</script><?php


// نبدأ تخزين المخرجات لضمان عدم خروج أي حرف قبل JSON
ob_start();

ini_set('display_errors', 0); // إخفاء الأخطاء عن المتصفح لضمان سلامة JSON
error_reporting(E_ALL);
session_start();

include("db.php");
require_once 'functions.php';
include("headers-policy.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use Dotenv\Dotenv;

// استدعاء الدوال الأساسية
redirectIfBlocked($pdo, getClientIP());
manage_csrf_token(); // ينشئ التوكن أو يتحقق منه

if (file_exists(DIR . '/keys.env')) {
$dotenv = Dotenv::createImmutable(DIR, 'keys.env');

_SERVER['REQUEST_METHOD'] === 'POST') {
ob_clean();
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'ملف الإعدادات مفقود.']);
exit;
}
http_response_code(500);
exit("Technical Error.");
}

$error_message = '';
$is_blocked = false;
$lock_remaining_time = 0;

// ---------------- معالجة طلب AJAX (المنطق الذي يعمل) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
ob_clean(); // تنظيف المخرجات السابقة
header('Content-Type: application/json; charset=utf-8');

code
Code
download
content_copy
expand_less
try {
    check_csrf(); // التحقق من التوكن القادم

    // 1. التحقق من Turnstile
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
    if (!validate_turnstile_response($turnstile_token)) {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        handle_password_reset_attempts($pdo, $email, 'forgot', true);
        
        // إرسال new_csrf لتحديثه في المتصفح للمحاولة التالية
        echo json_encode([
            'status' => 'error', 
            'message' => "فشل التحقق الأمني (CAPTCHA). حاول مرة أخرى.",
            'new_csrf' => $_SESSION['csrf_token'] 
        ]);
        exit;
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    
    // 2. التحقق من التكرار (Rate Limit)
    $rate_limit_check = handle_password_reset_attempts($pdo, $email, 'forgot', false);
    
    if ($rate_limit_check['blocked']) {
        echo json_encode([
            'status' => 'blocked', 
            'message' => "تجاوزت الحد المسموح من المحاولات.",
            'remaining_seconds' => $rate_limit_check['remaining_seconds'],
            'new_csrf' => $_SESSION['csrf_token']
        ]);
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handle_password_reset_attempts($pdo, $email, 'forgot', true);
        echo json_encode([
            'status' => 'error', 
            'message' => "صيغة البريد الإلكتروني غير صحيحة.",
            'new_csrf' => $_SESSION['csrf_token']
        ]);
        exit;
    } else {
        // المحاولة صحيحة
        handle_password_reset_attempts($pdo, $email, 'forgot', true);
        $token = create_password_reset_token($pdo, $email);
        
        if ($token) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = (int)$_ENV['SMTP_PORT'];
                $mail->CharSet = 'UTF-8';

                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Abdolwahab Accessories');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'استعادة كلمة المرور - Abdolwahab Accessories';
                
                $reset_link = "http://localhost:8088/myproject/wordpress/reset_password.php?token=" . urlencode($token);
                $year = date('Y');

                // --- تصميم الإيميل الجديد (Abdolwahab/Vynix) ---
                $email_template = "
                <div style='font-family: Arial, sans-serif; direction: rtl; text-align: right; background-color: #f8f8f8; padding: 40px 0;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e5e5e5;'>
                        
                        <!-- Header -->
                        <div style='background-color: #000000; padding: 30px; text-align: center;'>
                            <h1 style='color: #ffffff; margin: 0; font-family: \"Playfair Display\", serif; letter-spacing: 1px; font-size: 24px;'>Abdolwahab</h1>
                            <p style='color: #C8A95A; margin: 5px 0 0; font-size: 10px; text-transform: uppercase; letter-spacing: 2px;'>Parfums & Accessories</p>
                        </div>

                        <!-- Body -->
                        <div style='padding: 40px 30px; color: #333333;'>
                            <h2 style='font-size: 20px; color: #000; margin-bottom: 20px;'>مرحباً بك،</h2>
                            <p style='font-size: 15px; line-height: 1.8; color: #555;'>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في متجر عبدالوهاب. لإكمال العملية، يرجى الضغط على الزر أدناه:</p>
                            
                            <div style='text-align: center; margin: 35px 0;'>
                                <a href='$reset_link' style='background-color: #000000; color: #C8A95A; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; display: inline-block; border: 1px solid #C8A95A;'>إعادة تعيين كلمة المرور</a>
                            </div>
                            
                            <p style='font-size: 13px; color: #777; margin-top: 30px;'>أو يمكنك نسخ الرابط التالي ولصقه في المتصفح:</p>
                            <p style='font-size: 12px; color: #000; word-break: break-all; background: #f4f4f4; padding: 10px; border-radius: 4px;'>$reset_link</p>
                        </div>

                        <!-- Footer -->
                        <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
                            <p style='font-size: 12px; color: #999; margin: 0 0 10px;'>&copy; $year Abdolwahab Accessories. جميع الحقوق محفوظة.</p>
                            <div style='margin-top: 15px; font-size: 11px; color: #aaa;'>
            <a href='https://www.primestore.ma' style='color: #C8A95A; text-decoration: none; font-weight: bold;'>Primestore</a>
                            </div>
                        </div>
                    </div>
                </div>";

                $mail->Body = $email_template;
                $mail->AltBody = "الرابط: $reset_link";

                $mail->send();
                getLogger('auth')->info('تم إرسال بريد إعادة تعيين كلمة المرور.', ['email' => $email]);            
            } catch (Exception $e) {
                getLogger('general')->error('فشل إرسال بريد إعادة تعيين كلمة المرور.', ['error' => $e->getMessage()]);
            }
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => "إذا كان البريد مسجلاً، سيتم إرسال التعليمات إليه.",
            'new_csrf' => $_SESSION['csrf_token']
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'خطأ غير متوقع.',
        'new_csrf' => $_SESSION['csrf_token']
    ]);
    exit;
}

}
ob_end_flush();
?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="public/images/favicon.svg">
  <title>استعادة كلمة المرور - Abdolwahab Accessories</title>

code
Code
download
content_copy
expand_less
<link rel="preconnect" href="https://cdn.tailwindcss.com">
<link rel="preconnect" href="https://challenges.cloudflare.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

<!-- CSS مطابق لصفحة Register تماماً -->
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

    .page-header {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center; 
        background-color: transparent; 
        z-index: 50;
        pointer-events: none;
    }
    .ltr-force { direction: ltr; display: inline-block; }
    .logo-container {
        display: flex;
        align-items: center;
        gap: 12px;
        direction: ltr; 
        pointer-events: auto;
    }
    .logo-img { height: 60px; width: auto; object-fit: contain; }
    .logo-text-group { display: flex; flex-direction: column; align-items: flex-start; color: #000; }
    .logo-main { font-size: 1.5rem; font-weight: 700; line-height: 1; letter-spacing: 0.05em; }
    .logo-sub { font-size: 0.65rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.15em; margin-top: 2px; color: #333; }

    @media (min-width: 768px) {
        .page-header { position: absolute; justify-content: flex-end; }
    }

    .auth-container {
        width: 100%;
        max-width: 440px; 
        padding: 2rem;
        position: relative;
        z-index: 100 !important;
    }

    /* حقول الإدخال */
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
    .custom-input:focus { outline: none; box-shadow: none; border-bottom-color: #C8A95A; }
    .custom-input::placeholder { color: #6b7280; font-size: 0.95rem; transition: color 0.3s; font-weight: 500; }

    /* الأزرار */
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
        background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%);
        transform: skewX(-25deg);
        transition: none; 
    }

    @media (hover: hover) and (min-width: 1024px) {
        .btn-primary-pro:not(:disabled):hover { background-color: #000; }
        .btn-primary-pro:not(:disabled):hover::after { animation: shine 0.75s ease-in-out forwards; }
    }
    @keyframes shine { 100% { left: 150%; } }
    .btn-primary-pro:disabled { background-color: #9ca3af; cursor: not-allowed; opacity: 0.7; box-shadow: none; }

    .form-wrapper { transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; opacity: 1; transform: translateY(0); }
    .error-box { background-color: transparent; border: none; padding: 0.5rem 0; margin-bottom: 0.5rem; width: 100%; }
    
    /* Animation for messages */
    .slide-in { animation: slideIn 0.3s ease-out forwards; }
    @keyframes slideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

code
Code
download
content_copy
expand_less
<!-- Header -->
<header class="page-header">
    <div class="logo-container">
        <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png" alt="شعار عبدالوهاب للعطور" class="logo-img">
        <div class="logo-text-group font-logo">
            <span class="logo-main">Abdolwahab</span>
            <span class="logo-sub">Accessories & Parfums</span>
        </div>
    </div>
</header>

<main class="auth-container">
    <div class="form-wrapper">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-900 font-cairo tracking-tight">استعادة كلمة المرور</h1>
        </div>
        
        <!-- منطقة عرض الرسائل -->
        <div id="message-container">
            <?php if ($is_blocked): ?>
                <div class="p-4 mb-6 border rounded-md bg-gray-50 text-gray-700 text-center">
                    <p class="text-sm">
                        عفواً، تم حظرك مؤقتاً.
                        <span id="countdown-timer" class="font-bold text-red-600"></span>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <form id="forgot-password-form" class="space-y-6" method="POST" action="forgot_password.php">
            
            <!-- هذا الحقل سيتم تحديثه تلقائياً بالجافاسكربت بعد كل محاولة -->
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="space-y-5">
                <div>
                    <input name="email" id="email" type="email" class="custom-input" placeholder="البريد الإلكتروني" required>
                </div>
            </div>

            <!-- الكابتشا -->
            <div id="shared-turnstile-widget" class="cf-turnstile scale-90" data-sitekey="<?php echo htmlspecialchars($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']); ?>" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired" style="display: flex; justify-content: center; margin-top: 1rem;"></div>

            <button type="submit" id="submit-button" class="btn-primary-pro" disabled>
                إرسال رابط الاستعادة
            </button>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <a href="register.php" class="font-bold lg:hover:underline transition-all" style="color:#C8A95A; font-size: 0.95rem;">العودة إلى تسجيل الدخول</a>
            </div>
        </form>
    </div>
</main>

<!-- JavaScript - نفس المنطق الذي كان يعمل تماماً -->
<script>
    let remainingTime = <?php echo (int)$lock_remaining_time; ?>;
    let timerInterval = null;

    document.addEventListener('DOMContentLoaded', function () {
        const isBlockedInitial = <?php echo json_encode($is_blocked); ?>;
        
        if (isBlockedInitial && remainingTime > 0) {
            disableForm();
            startCountdown();
        }

        const form = document.getElementById('forgot-password-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                handleAjaxSubmit(form);
            });
        }
    });

    function handleAjaxSubmit(form) {
        const submitBtn = document.getElementById('submit-button');
        const originalBtnText = submitBtn.innerText;
        const messageContainer = document.getElementById('message-container');
        const csrfInput = document.getElementById('csrf_token');

        // 1. حالة التحميل
        submitBtn.disabled = true;
        submitBtn.innerText = 'جاري المعالجة...';
        messageContainer.innerHTML = ''; 

        const formData = new FormData(form);

        fetch('forgot_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // التأكد من أن الرد هو JSON صحيح
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Invalid JSON:", text);
                    throw new Error("حدث خطأ في الاتصال بالخادم.");
                }
            });
        })
        .then(data => {
            // ✨ المنطق الذي يعمل: تحديث CSRF Token للمحاولة القادمة
            if (data.new_csrf && csrfInput) {
                csrfInput.value = data.new_csrf;
            }

            if (data.status === 'success') {
                renderMessage('success', data.message);
                form.reset(); 
                // نعيد وضع التوكن الجديد لأن reset() قد تمسحه
                if (data.new_csrf) csrfInput.value = data.new_csrf;
            } 
            else if (data.status === 'blocked') {
                remainingTime = parseInt(data.remaining_seconds);
                renderMessage('blocked', data.message);
                disableForm();
                startCountdown();
            } 
            else {
                // حالة الخطأ العادي
                renderMessage('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            renderMessage('error', 'حدث خطأ غير متوقع، يرجى تحديث الصفحة والمحاولة مجدداً.');
        })
        .finally(() => {
            // ✨ أهم جزء: إعادة تعيين الكابتشا دائماً ليعمل في المحاولة الثانية
            if (window.turnstile) {
                window.turnstile.reset();
            }

            // نعيد الزر لحالته الأصلية إذا لم يكن محظوراً
            if (remainingTime <= 0) {
                submitBtn.innerText = originalBtnText;
                // ملاحظة: يبقى الزر disabled حتى يقوم المستخدم بحل الكابتشا الجديدة وتفعيل onTurnstileSuccess
            }
        });
    }

function renderMessage(type, text) {
const container = document.getElementById('message-container');
let colorClass = '';

code
Code
download
content_copy
expand_less
// تحديد لون النص فقط (بدون خلفيات)
        if (type === 'success') colorClass = 'text-green-600';
        else if (type === 'error') colorClass = 'text-red-600';
        else if (type === 'blocked') colorClass = 'text-gray-700';

        // التعديلات:
        // 1. bg-transparent: خلفية شفافة
        // 2. text-xs: خط صغير
        // 3. flex ... gap-1: لضمان بقاء العداد بجانب النص في نفس السطر
        let html = `
            <div class="mb-4 bg-transparent text-center slide-in">
                <div class="flex flex-wrap justify-center items-center gap-1 ${colorClass} text-xs font-bold">
                    <span>${text}</span>
                    ${type === 'blocked' ? '<span id="countdown-timer" class="text-red-600 font-extrabold ltr-force"></span>' : ''}
                </div>
            </div>`;
        
        container.innerHTML = html;
    }
    function formatTime(totalSeconds) {
        if (totalSeconds < 0) totalSeconds = 0;
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function startCountdown() {
        const el = document.getElementById('countdown-timer');
        if(el) el.textContent = formatTime(remainingTime);

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            remainingTime--;
            const elUpdated = document.getElementById('countdown-timer');
            if (elUpdated) elUpdated.textContent = formatTime(remainingTime);

            if (remainingTime <= 0) {
                clearInterval(timerInterval);
                window.location.reload(); 
            }
        }, 1000);
    }

    function disableForm() {
        const btn = document.getElementById('submit-button');
        const emailInput = document.getElementById('email');
        if(btn) btn.disabled = true;
        if(emailInput) emailInput.disabled = true;
    }

    // عند حل الكابتشا
    function onTurnstileSuccess(token) {
        if (remainingTime <= 0) {
            document.getElementById('submit-button').disabled = false;
        }
    }

    // عند انتهاء صلاحيتها
    function onTurnstileExpired() {
        document.getElementById('submit-button').disabled = true;
    }
</script>
</body>
</html>
<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;
try { if (file_exists(__DIR__ . '/apikeys.env')) { $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env'); $dotenv->load(); } } catch (Exception $e) {}


$woocommerce = new Automattic\WooCommerce\Client(
$_ENV['wordpress_url'], $_ENV['consumer_key'], $_ENV['secret_key'],
['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 30]
);

𝑖
𝑠
𝐿
𝑜
𝑔
𝑔
𝑒
𝑑
𝐼
𝑛
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
isLoggedIn=isset(
_SESSION['user_id']);
$cart_items = [];

if ($isLoggedIn) {
$stmt = $pdo->prepare("SELECT * FROM user_cart WHERE user_id = ? ORDER BY id DESC");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

𝑐
𝑎
𝑟
𝑡
𝑖
𝑡
𝑒
𝑚
𝑠
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
cart
i
	​

tems=isset(
_SESSION['guest_cart']) ? $_SESSION['guest_cart'] : [];
}
// حساب العدد الإجمالي للمنتجات من قاعدة البيانات مباشرة

𝑡
𝑜
𝑡
𝑎
𝑙
𝑑
𝑏
𝑞
𝑡
𝑦
=
0
;
𝑓
𝑜
𝑟
𝑒
𝑎
𝑐
ℎ
(
total
d
	​

b
q
	​

ty=0;foreach(
cart_items as $ci) { $total_db_qty += $ci['quantity']; }
// إرسال العدد في حقل مخفي ليقرأه الجافا سكريبت
echo '<input type="hidden" id="db-cart-total-count" value="' . $total_db_qty . '">';
// --- حالة السلة فارغة ---
if (empty($cart_items)) {
echo '<div class="text-center py-20">
<img src="https://res.cloudinary.com/dmakzfsc4/image/upload/v1768252470/empty_cart_abbrh8.png" class="w-20 mx-auto mb-4">
<h3 class="text-gray-500 font-bold" style="font-family:\'Cairo\';">سلتك فارغة حالياً</h3>
</div>';
echo '<input type="hidden" id="hidden-cart-total" value="0.00 د.م">';
exit;
}

𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑖
𝑑
𝑠
=
𝑎
𝑟
𝑟
𝑎
𝑦
𝑐
𝑜
𝑙
𝑢
𝑚
𝑛
(
product
i
	​

ds=array
c
	​

olumn(
cart_items, 'product_id');
try {
$all_products = 
𝑤
𝑜
𝑜
𝑐
𝑜
𝑚
𝑚
𝑒
𝑟
𝑐
𝑒
−
>
𝑔
𝑒
𝑡
(
′
𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑠
′
,
[
′
𝑖
𝑛
𝑐
𝑙
𝑢
𝑑
𝑒
′
=
>
𝑎
𝑟
𝑟
𝑎
𝑦
𝑢
𝑛
𝑖
𝑞
𝑢
𝑒
(
woocommerce−>get(
′
products
′
,[
′
include
′
=>array
u
	​

nique(
product_ids), 'per_page' => 100]);

𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑠
𝑚
𝑎
𝑝
=
[
]
;
𝑓
𝑜
𝑟
𝑒
𝑎
𝑐
ℎ
(
products
m
	​

ap=[];foreach(
all_products as $p) { 
𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑠
𝑚
𝑎
𝑝
[
products
m
	​

ap[
p->id] = $p; }

code
Code
download
content_copy
expand_less
$total_price = 0;
echo '<div class="space-y-4">';
foreach ($cart_items as $item) {
    $p = $products_map[$item['product_id']] ?? null;
    if (!$p) continue;

    $price = floatval($p->price);
    $subtotal = $price * $item['quantity'];
    $total_price += $subtotal;
    $img = !empty($p->images) ? $p->images[0]->src : '';
    // معرف السطر (cart_id للمسجل أو cart_key للزائر)
    $row_id = $isLoggedIn ? $item['id'] : $item['id']; 
    ?>
    <div class="cart-item-row product-card-professional p-4 flex flex-row items-start gap-4 bg-white border border-gray-100 rounded-lg relative" data-p-id="<?php echo $item['product_id']; ?>"data-qty="<?php echo $item['quantity']; ?>">
        <div class="w-24 h-32 flex-shrink-0 overflow-hidden relative rounded-lg border border-gray-100">
            <img src="<?php echo $img; ?>" class="w-full h-full object-cover">
            <button onclick="removeFromCart('<?php echo $row_id; ?>', this)" class="remove-product-icon active absolute top-1.5 left-1.5 w-7 h-7 bg-white/90 shadow-sm text-black rounded-full flex items-center justify-center">
                <i class="ph ph-trash text-base"></i>
            </button>
        </div>
        <div class="flex flex-col flex-grow text-right">
            <h4 class="font-bold text-sm text-gray-900 leading-tight mb-1"><?php echo $p->name; ?></h4>
            <p class="category-text text-xs font-semibold mb-2" style="color: #C8A95A !important;">
                <?php if(!empty($item['selected_size'])) echo 'المقاس: '.$item['selected_size']; ?>
                <?php if(!empty($item['selected_color'])) echo ' | اللون: '.$item['selected_color']; ?>
            </p>
            <div class="flex items-center justify-between mt-auto">
         <div class="flex items-center border border-gray-200 px-2 py-1" style="border-radius: 50px !important;">
<!-- زر الزيادة: نرسل رقم 1 -->
<button onclick="updateCartQty('<?php echo $row_id; ?>', 1, this)" class="ph ph-plus text-xs px-1"></button>

<span class="px-2 text-sm font-bold"><?php echo $item['quantity']; ?></span>

<!-- زر النقصان: نرسل رقم -1 -->
<button onclick="updateCartQty('<?php echo $row_id; ?>', -1, this)" class="ph ph-minus text-xs px-1"></button>
</div>
                    <span class="font-bold text-base text-gray-900"><?php echo number_format($price, 2); ?> د.م</span>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    echo '<input type="hidden" id="hidden-cart-total" value="' . number_format($total_price, 2) . ' د.م">';
} catch (Exception $e) { echo 'خطأ في جلب البيانات'; }<?php
session_start();
require_once 'db.php';
require_once 'vendor/autoload.php';


use Dotenv\Dotenv;
try { if (file_exists(DIR . '/apikeys.env')) { $dotenv = Dotenv::createImmutable(DIR, 'apikeys.env'); $dotenv->load(); } } catch (Exception $e) {}

if (!isset($_SESSION['user_id'])) { exit; }

$woocommerce = new Automattic\WooCommerce\Client(
$_ENV['wordpress_url'], $_ENV['consumer_key'], $_ENV['secret_key'],
['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 30]
);

$stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ? ORDER BY id DESC");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
_SESSION['user_id']]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($ids)) {
echo '<div class="text-center py-20">
<img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252544/empty_wishlist_ciqog9.png" class="w-20 mx-auto  mb-4 empty-menu-icon">
<h3 class="text-gray-500 font-bold" style="font-family:\'Cairo\';">مفضلتك فارغة</h3>
</div>';
} else {
try {
$products = $woocommerce->get('products', ['include' => array_map('intval', $ids), 'per_page' => 100]);

𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑠
=
𝑗
𝑠
𝑜
𝑛
𝑑
𝑒
𝑐
𝑜
𝑑
𝑒
(
𝑗
𝑠
𝑜
𝑛
𝑒
𝑛
𝑐
𝑜
𝑑
𝑒
(
products=json
d
	​

ecode(json
e
	​

ncode(
products), true);
$_SESSION['wishlist_cache'] = $products;

echo '<div class="text-xs font-bold mb-4 text-right" style="font-size: 14px; font-family: \'Cairo\';">لديك <span id="wishlist-sidebar-count-text">'.count(
𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑠
)
.
′
<
/
𝑠
𝑝
𝑎
𝑛
>
عناصر
.
<
/
𝑑
𝑖
𝑣
>
′
;
𝑒
𝑐
ℎ
𝑜
′
<
𝑑
𝑖
𝑣
𝑐
𝑙
𝑎
𝑠
𝑠
=
"
𝑠
𝑝
𝑎
𝑐
𝑒
−
𝑦
−
4
"
>
′
;
𝑓
𝑜
𝑟
𝑒
𝑎
𝑐
ℎ
(
products).
′
</span>عناصر.</div>
′
;echo
′
<divclass="space−y−4">
′
;foreach(
products as $item) {

𝑐
𝑎
𝑡
=
!
𝑒
𝑚
𝑝
𝑡
𝑦
(
cat=!empty(
item['categories']) ? $item['categories'][0]['name'] : 'منتج';

𝑖
𝑚
𝑔
=
!
𝑒
𝑚
𝑝
𝑡
𝑦
(
img=!empty(
item['images']) ? $item['images'][0]['src'] : '';
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
}<?php
// headers-policy.php - رؤوس أمان HTTP متوافقة مع Tailwind CSS

// منع الإطارات (Clickjacking)
header('X-Frame-Options: DENY');

// منع كشف نوع المحتوى (MIME Sniffing)
header('X-Content-Type-Options: nosniff');

// حماية XSS القديمة (إذا كان المتصفح يدعمها)
header('X-XSS-Protection: 1; mode=block');

// سياسة الإحالة (Referrer) - تقلل تسريب URL
header('Referrer-Policy: strict-origin-when-cross-origin');

// سياسة نقل آمن (HSTS) - لـ HTTPS فقط؛ تجاهل على localhost
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// سياسة الإذن (Permissions) - تقييد ميزات المتصفح
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), fullscreen=()');

// منع تخزين في Cache للصفحات الحساسة
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// تعطيل عرض الأخطاء في الإنتاج (غيّر إلى '1' على localhost للتصحيح)
if (!isset(
𝑆
𝐸
𝑅
𝑉
𝐸
𝑅
[
′
𝐻
𝑇
𝑇
𝑃
𝐻
𝑂
𝑆
𝑇
′
]
)
∣
∣
𝑠
𝑡
𝑟
𝑝
𝑜
𝑠
(
S
	​

ERVER[
′
HTTP
H
	​

OST
′
])∣∣strpos(
_SERVER['HTTP_HOST'], 'localhost') === false) {
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
} else {
ini_set('display_errors', '0'); // ساعد في التصحيح على localhost
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
}
?><?php
ob_start();
require_once 'logger_setup.php';

// إعدادات الكوكيز والجلسة
session_set_cookie_params([
'path' => '/',
'domain' => '',
'secure' => true,
'httponly' => true,
'samesite' => 'Strict'
]);

session_start();
include("db.php");
require_once 'functions.php';
require_once 'headers-policy.php';

// ❌ قمنا بحذف الشرط القديم الذي كان يرفض الطلب ويعيدك للرئيسية
// if (
𝑆
𝐸
𝑅
𝑉
𝐸
𝑅
[
′
𝑅
𝐸
𝑄
𝑈
𝐸
𝑆
𝑇
𝑀
𝐸
𝑇
𝐻
𝑂
𝐷
′
]
!
=
=
′
𝑃
𝑂
𝑆
𝑇
′
∣
∣
!
𝑖
𝑠
𝑠
𝑒
𝑡
(
S
	​

ERVER[
′
REQUEST
M
	​

ETHOD
′
]!==
′
POST
′
∣∣!isset(
_POST['logout'])) { ... }

// ✅ بدلاً من ذلك، نتحقق فقط من وجود جلسة
if (!isset($_SESSION['user_id'])) {
header("Location: index.php");
exit;
}

// ** ملاحظة أمان **
// إذا أردت تفعيل CSRF مع GET، يجب إرسال التوكن في الرابط: logout.php?token=xyz
// للتسهيل عليك الآن، سنقوم بتسجيل الخروج مباشرة.

// 1. التقاط المعلومات للوج (Log)
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'N/A';
$ip = getClientIP();
$selector = null;

if (isset($_COOKIE['remember_token'])) {

𝑐
𝑜
𝑜
𝑘
𝑖
𝑒
𝑑
𝑎
𝑡
𝑎
=
𝑏
𝑎
𝑠
𝑒
64
𝑑
𝑒
𝑐
𝑜
𝑑
𝑒
(
cookie
d
	​

ata=base64
d
	​

ecode(
_COOKIE['remember_token'], true);
if (
cookie_data, ':') === 1) {
list($selector, $validator) = explode(':', $cookie_data, 2);
}
}

// 2. التنظيف
if (
pdo, $user_id, $selector);
}

setcookie('remember_token', '', time() - 3600, '/', '', true, true);
session_unset();
session_destroy();

// 3. تسجيل الحدث
if ($user_id) {
$authLogger = getLogger('auth');
$authLogger->info('User logged-out successfully.', [
'user_id' => $user_id,
'username' => $username,
'ip' => $ip
]);
}

// ✅ التوجيه النهائي لصفحة التسجيل
ob_end_clean();
header("Location: register.php?logged_out=1");
exit;
?><?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';
require_once 'functions.php';

// تحقق من وجود autoload
// Monolog MODIFIED: التحقق من وجود autoload مع تسجيل حرج
if (!file_exists(DIR . '/vendor/autoload.php')) {
getLogger('setup')->critical('FATAL ERROR: vendor/autoload.php not found. Run "composer install".');
// يمكنك عرض صفحة خطأ مخصصة هنا للمستخدم
http_response_code(500);
exit("A technical error occurred. please try again later.");
}
require DIR . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// تحقق من وجود ملف keys.env
if (!file_exists(DIR . '/keys.env')) {
getLogger('setup')->critical('FATAL ERROR: keys.env file not found.', ['path' => DIR]);
http_response_code(500);
exit("A technical error occurred. please try again later.");
}

// تحميل ملف keys.env
try {
$dotenv = Dotenv::createImmutable(DIR, 'keys.env');
$dotenv->load();
} catch (Exception $e) {
getLogger('setup')->critical('Failed to load keys.env file.', ['error' => $e->getMessage()]);
http_response_code(500);
exit("A technical error occurred. please try again later.");
}

$ip = getClientIP();
$email = $_SESSION['user_email'] ?? $_POST['email'] ?? '';
$otp = $_SESSION['otp_code'] ?? '';
$username = $_SESSION['user_username'] ?? '';

𝑖
𝑠
𝑟
𝑒
𝑠
𝑒
𝑛
𝑑
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
is
r
	​

esend=isset(
_GET['resent']) && $_GET['resent'] == 1;

𝑑
𝑒
𝑣
𝑖
𝑐
𝑒
𝑐
ℎ
𝑎
𝑛
𝑔
𝑒
𝑑
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
device
c
	​

hanged=isset(
_GET['device_changed']) && $_GET['device_changed'] == 1;

// التحقق من وجود البريد الإلكتروني
if (empty($email)) {
$error_message =  "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
getLogger('auth')->warning('sendmail.php accessed with no email in session.', ['ip' => $ip]);
header("Location: otp-page.php?error=");
exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

𝑒
𝑟
𝑟
𝑜
𝑟
𝑚
𝑒
𝑠
𝑠
𝑎
𝑔
𝑒
=
"
𝐼
𝑛
𝑣
𝑎
𝑙
𝑖
𝑑
𝑒
𝑚
𝑎
𝑖
𝑙
𝑜
𝑟
𝑝
𝑎
𝑠
𝑠
𝑤
𝑜
𝑟
𝑑
.
"
;
𝑙
𝑜
𝑔
𝑜
𝑡
𝑝
𝑎
𝑡
𝑡
𝑒
𝑚
𝑝
𝑡
(
error
m
	​

essage="Invalidemailorpassword.";log
o
	​

tp
a
	​

ttempt(
pdo, $ip, $email, 'otp_resend', 'failed');
getLogger('auth')->warning('sendmail.php accessed with an invalid email format.', ['ip' => $ip, 'email_attempt' => $email]);
header("Location: otp-page.php?error=");
exit();
}

𝑢
𝑠
𝑒
𝑟
𝑛
𝑎
𝑚
𝑒
=
𝑓
𝑖
𝑙
𝑡
𝑒
𝑟
𝑣
𝑎
𝑟
(
username=filter
v
	​

ar(
username, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
if (empty($username)) {
$username = 'Anonymouse';
}

if ($device_changed) {

𝑒
𝑟
𝑟
𝑜
𝑟
𝑚
𝑒
𝑠
𝑠
𝑎
𝑔
𝑒
=
"
حدثخطأفنيغيرمتوقع،يرجىالمحاولةلاحقاً
.
"
;
𝑙
𝑜
𝑔
𝑜
𝑡
𝑝
𝑎
𝑡
𝑡
𝑒
𝑚
𝑝
𝑡
(
error
m
	​

essage="حدثخطأفنيغيرمتوقع،يرجىالمحاولةلاحقاً.";log
o
	​

tp
a
	​

ttempt(
pdo, $ip, $email, 'otp_resend', 'failed');
getLogger('security')->warning('OTP send aborted due to device/IP change.', [
'ip' => $ip,
'email' => $email
]);

code
Code
download
content_copy
expand_less
// Return JSON for AJAX
echo json_encode([
    "status" => "redirect",
    "location" => "otp-page.php?error=" . urlencode($error_message)
]);
exit();

}

$mail = new PHPMailer(true);

ob_start();
try {
$mail->isSMTP();
$mail->Host = $_ENV['SMTP_HOST'];
$mail->SMTPAuth = true;
$mail->Username = $_ENV['SMTP_USERNAME'];
$mail->Password = $_ENV['SMTP_PASSWORD'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

𝑚
𝑎
𝑖
𝑙
−
>
𝑃
𝑜
𝑟
𝑡
=
(
𝑖
𝑛
𝑡
)
mail−>Port=(int)
_ENV['SMTP_PORT'];

code
Code
download
content_copy
expand_less
$mail->SMTPDebug = 0;
$mail->Debugoutput = function ($str, $level) {
    getLogger('mail_debug')->debug("PHPMailer", ['level' => $level, 'message' => $str]);
};

$mail->setFrom($_ENV['SMTP_USERNAME'], 'رمز التحقق - Abdolwahab Accessories ');
$mail->addAddress($email, $username);
$mail->CharSet = 'UTF-8';
$mail->isHTML(true);

$mail->Subject = $is_resend ? 'إعادة إرسال رمز التحقق' : 'رمز التحقق الخاص بك';

$mail->Body = "

<div style='font-family: Arial, sans-serif; direction: rtl; text-align: right; background-color: #f8f8f8; padding: 40px 0;'>
    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e5e5e5;'>

code
Code
download
content_copy
expand_less
<!-- Header -->
    <div style='background-color: #000000; padding: 30px; text-align: center;'>
        <h1 style='color: #ffffff; margin: 0; font-family: \"Playfair Display\", serif; letter-spacing: 1px; font-size: 24px;'>Abdolwahab</h1>
        <p style='color: #C8A95A; margin: 5px 0 0; font-size: 10px; text-transform: uppercase; letter-spacing: 2px;'>Parfums & Accessories</p>
    </div>

    <!-- Body -->
    <div style='padding: 40px 30px; color: #333333;'>
        
        <h2 style='font-size: 20px; color: #000; margin-bottom: 20px; text-align:center;'>رمز التحقق الخاص بك</h2>

        <p style='font-size: 15px; line-height: 1.8; color: #555;'>
            مرحبًا 
        </p>

        <p style='font-size: 16px; color: #333;'>
            رمز التحقق الخاص بك هو:
        </p>

        <div style='text-align: center; margin: 30px 0;'>
            <span style='
                display: inline-block;
                padding: 14px 30px;
                font-size: 22px;
                font-weight: bold;
                background-color: #000000;
                color: #C8A95A;
                border-radius: 50px;
                border: 1px solid #C8A95A;
                letter-spacing: 4px;
            '>" . htmlspecialchars($otp, ENT_QUOTES, "UTF-8") . "</span>
        </div>

        <p style='font-size: 14px; color: #555; line-height: 1.8;'>
            هذا الرمز صالح لمدة <strong>5 دقائق</strong>.<br>
            الرجاء إدخاله في صفحة التحقق لإكمال عملية التسجيل.
        </p>

        <p style='font-size: 14px; color: #999;'>
            إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد الإلكتروني.
        </p>

    </div>

    <!-- Footer -->
    <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
        <p style='font-size: 12px; color: #999; margin: 0 0 10px;'>&copy; " . date('Y') . " Abdolwahab Accessories. جميع الحقوق محفوظة.</p>
        <div style='margin-top: 15px; font-size: 11px; color: #aaa;'>
            Dev & Design by 
            <a href='https://www.primestore.ma' style='color: #C8A95A; text-decoration: none; font-weight: bold;'>Primestore</a>
        </div>
    </div>

</div>
</div>
";

code
Code
download
content_copy
expand_less
$mail->AltBody = "مرحبًا " .  "،\n\nرمز التحقق الخاص بك هو: " . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "\n\nهذا الرمز صالح لمدة 5 دقائق. الرجاء إدخاله في صفحة التحقق لإكمال التسجيل.\n\nإذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد.\n\nشكرًا،\nفريق التطبيق";

if ($mail->send()) {
    $_SESSION['otp_resend_mode'] = true;
    $_SESSION['is_initial_otp'] = !$is_resend;
    increment_attempts_otp($pdo, $ip, $email, 'otp_resend');
    log_otp_attempt($pdo, $ip, $email, 'otp_resend', 'success');
    getLogger('mail')->info('OTP email sent successfully.', [
        'email' => $email,
        'ip' => $ip,
        'is_resend' => $is_resend
    ]);
    ob_get_clean();
    $redirect_url = $is_resend ? "otp-page.php?resent=1" : "otp-page.php";
    header("Location: $redirect_url");
    exit();
} else {
    throw new Exception("فشل إرسال البريد: " . $mail->ErrorInfo);
}

} catch (Exception $e) {
$error_message =  "حدث خطأ فني غير متوقع، يرجى المحاولة لاحقاً.";
getLogger('mail')->error('PHPMailer failed to send email.', [
'email' => $email,
'ip' => $ip,
'error_info' => $mail->ErrorInfo, // خطأ PHPMailer المحدد
'exception_message' => 
𝑒
−
>
𝑔
𝑒
𝑡
𝑀
𝑒
𝑠
𝑠
𝑎
𝑔
𝑒
(
)
]
)
;
𝑙
𝑜
𝑔
𝑜
𝑡
𝑝
𝑎
𝑡
𝑡
𝑒
𝑚
𝑝
𝑡
(
e−>getMessage()]);log
o
	​

tp
a
	​

ttempt(
pdo, $ip, $email, 'otp_resend', 'failed');
ob_get_clean();
header("Location: otp-page.php?error=");
exit();
}
?><?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');
error_reporting(0);

function send_final_response($status, $message) {
$new_token = generate_csrf_token();
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
if (!isset(
𝑃
𝑂
𝑆
𝑇
[
′
𝑐
𝑠
𝑟
𝑓
𝑡
𝑜
𝑘
𝑒
𝑛
′
]
)
∣
∣
!
𝑖
𝑠
𝑠
𝑒
𝑡
(
P
	​

OST[
′
csrf
t
	​

oken
′
])∣∣!isset(
_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
send_final_response('error', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى.');
}

// 2. فحص البريد الإلكتروني

𝑒
𝑚
𝑎
𝑖
𝑙
=
𝑓
𝑖
𝑙
𝑡
𝑒
𝑟
𝑣
𝑎
𝑟
(
email=filter
v
	​

ar(
_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
send_final_response('error', 'البريد الإلكتروني المدخل غير صحيح.');
}

// 3. فحص الـ Rate Limit
$config = include('config.php');
$limits = $config['rate_limits']['subscribe'];
$ip = getClientIP();

try {
$stmt = $pdo->prepare("SELECT attempts, last_attempt FROM ip_attemptss WHERE ip = ? AND action_type = 'subscribe' LIMIT 1");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

code
Code
download
content_copy
expand_less
if ($row) {
    $time_passed = time() - $row['last_attempt'];
    $current_attempts = ($time_passed > $limits['interval']) ? 0 : $row['attempts'];
    if ($current_attempts >= $limits['attempts']) {
        send_final_response('error', 'لقد تجاوزت حد المحاولات المسموح به.');
    }
}

} catch (PDOException $e) {}

// تم حذف خطوة التحقق من الكابتشا (Turnstile) هنا لتبسيط العملية

// 5. تسجيل زيادة المحاولات
try {
$stmt = $pdo->prepare("INSERT INTO ip_attemptss (ip, action_type, attempts, last_attempt, updated_at)
VALUES (?, 'subscribe', 1, UNIX_TIMESTAMP(), NOW())
ON DUPLICATE KEY UPDATE
attempts = IF(UNIX_TIMESTAMP() - last_attempt > ?, 1, attempts + 1),
last_attempt = UNIX_TIMESTAMP(),
updated_at = NOW()");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
ip, $limits['interval']]);
} catch (PDOException $e) {}

// 6. تنفيذ عملية الاشتراك
try {
$stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ? LIMIT 1");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
email]);
if ($stmt->rowCount() > 0) {
$_SESSION['has_subscribed'] = true;
send_final_response('success', 'أنت مشترك بالفعل في قائمتنا البريدية.');
}

code
Code
download
content_copy
expand_less
$stmt = $pdo->prepare("INSERT INTO subscribers (email, ip_address, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$email, $ip]);

$_SESSION['has_subscribed'] = true;
send_final_response('success', 'تم اشتراكك بنجاح! شكراً لثقتك.');

} catch (PDOException $e) {
send_final_response('error', 'حدث خطأ فني أثناء حفظ البيانات.');
}<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['status' => 'need_login']); exit; }

$user_id = $_SESSION['user_id'];

𝑝
𝑟
𝑜
𝑑
𝑢
𝑐
𝑡
𝑖
𝑑
=
𝑖
𝑠
𝑠
𝑒
𝑡
(
product
i
	​

d=isset(
_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id > 0) {
try {
$stmt = $pdo->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");

𝑠
𝑡
𝑚
𝑡
−
>
𝑒
𝑥
𝑒
𝑐
𝑢
𝑡
𝑒
(
[
stmt−>execute([
user_id, $product_id]);
$row = $stmt->fetch();

code
Code
download
content_copy
expand_less
if ($row) {
        $pdo->prepare("DELETE FROM user_wishlist WHERE id = ?")->execute([$row['id']]);
        $action = 'removed';
    } else {
        // نترك الـ id فارغ لكي تأخذه القاعدة تلقائياً AUTO_INCREMENT
        $pdo->prepare("INSERT INTO user_wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
        $action = 'added';
    }

    unset($_SESSION['wishlist_cache']); 
    $count = $pdo->prepare("SELECT COUNT(*) FROM user_wishlist WHERE user_id = ?");
    $count->execute([$user_id]);
    
    echo json_encode(['status' => 'success', 'action' => $action, 'count' => $count->fetchColumn()]);
} catch (Exception $e) { echo json_encode(['status' => 'error']); }

}<?php
// منع أي مخرجات غريبة (Errors/Warnings) من إفساد الـ JSON
ob_start();
session_start();
require_once 'db.php';

// ضبط الرأس ليكون JSON
header('Content-Type: application/json');

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
ob_clean();
echo json_encode(['status' => 'need_login']);
exit;
}

$user_id = $_SESSION['user_id'];

try {
$pdo->beginTransaction();

code
Code
download
content_copy
expand_less
// 1. جلب المنتجات من المفضلة
$stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($items)) {
    $pdo->rollBack();
    ob_clean();
    echo json_encode(['status' => 'empty']);
    exit;
}

// 2. نقل كل منتج إلى السلة
foreach ($items as $product_id) {
    // التحقق إذا كان المنتج موجود مسبقاً في السلة (بدون مقاس أو لون محدد)
    $checkCart = $pdo->prepare("SELECT id FROM user_cart WHERE user_id = ? AND product_id = ? AND variation_id = 0");
    $checkCart->execute([$user_id, (int)$product_id]);
    $existing = $checkCart->fetch();

    if ($existing) {
        // تحديث الكمية فقط
        $pdo->prepare("UPDATE user_cart SET quantity = quantity + 1 WHERE id = ?")->execute([$existing['id']]);
    } else {
        // إضافة منتج جديد - تأكد أن أسماء الأعمدة مطابقة لجدولك (selected_size, selected_color)
        // إذا كان جدولك يستخدم 'attributes' فغيرها هنا، لكن بناءً على ملف get-cart-items فهي غالباً منفصلة
        $pdo->prepare("INSERT INTO user_cart (user_id, product_id, variation_id, quantity, selected_size, selected_color) VALUES (?, ?, 0, 1, '', '')")
            ->execute([$user_id, (int)$product_id]);
    }
}

// 3. حذف المنتجات من المفضلة بعد النقل بنجاح
$pdo->prepare("DELETE FROM user_wishlist WHERE user_id = ?")->execute([$user_id]);

$pdo->commit();

if(isset($_SESSION['cart_cache'])) unset($_SESSION['cart_cache']);

// مسح كاش السلة والمفضلة لضمان تحديث الأرقام فوراً
unset(
𝑆
𝐸
𝑆
𝑆
𝐼
𝑂
𝑁
[
′
𝑐
𝑎
𝑟
𝑡
𝑐
𝑎
𝑐
ℎ
𝑒
′
]
)
;
𝑢
𝑛
𝑠
𝑒
𝑡
(
S
	​

ESSION[
′
cart
c
	​

ache
′
]);unset(
_SESSION['wishlist_cache']);
session_write_close(); // إجبار المتصفح على حفظ التغييرات فوراً
ob_clean();
echo json_encode(['status' => 'success']);
exit;

} catch (Exception 
pdo->inTransaction()) $pdo->rollBack();
ob_clean();
// إرسال رسالة الخطأ الحقيقية للمساعدة في الديباجينج (يمكنك تغييرها لاحقاً)
echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
exit;
}consumer_key=ck_d2f5ed4472a677545445e9c82423ed297b578aa1cb7420
secret_key=cs_68b481f4c34356be74554545145f3418b8ece6401aff09
wordpress_url=http://localhost:8088/my-project/wordpress/wordpress
whatsapp_number=+212688-40888554
ck_6370e046642c14b78e9cdgdfgdfgdfg59ccd3f5c3a7940a127da
cs_2f7735843d48f981f07e115b4fc36da585a21d40
SMTP_DBHOST=localhost
SMTP_DBNAME=stores
SMTP_USERNAME=soufyantarach44kjhjg@gmail.com
SMTP_DBUSERNAME=root
SMTP_DBPASSWORD=
SMTP_PASSWORD="vncl owsy bbvo wjfdgdfv"
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465

Cloudflare Turnstile Keys

CLOUDFLARE_TURNSTILE_SITE_KEY="0x4AAAAAAB6EwGuBkcNho5Ngdfdfgddg1"
CLOUDFLARE_TURNSTILE_SECRET_KEY="0x4AAAAAAB6EwLIwvSXMMuzqykhhgnP4en0"