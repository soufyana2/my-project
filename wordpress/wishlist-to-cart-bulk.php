<?php
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
unset($_SESSION['cart_cache']);
unset($_SESSION['wishlist_cache']);
session_write_close(); // إجبار المتصفح على حفظ التغييرات فوراً
    ob_clean();
    echo json_encode(['status' => 'success']);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    ob_clean();
    // إرسال رسالة الخطأ الحقيقية للمساعدة في الديباجينج (يمكنك تغييرها لاحقاً)
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}