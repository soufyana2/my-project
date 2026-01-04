<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'يجب تسجيل الدخول']);
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
        echo json_encode(['status' => 'empty']);
        exit;
    }

    foreach ($items as $product_id) {
        // قيم صارمة لضمان التطابق مع دالة updateCart والـ API الأصلي
        $v_id = 0;           // يجب أن يكون 0 (Integer) وليس NULL
        $attr = "";          // يجب أن يكون نص فارغ (Empty String) وليس NULL

        // 2. البحث عن المنتج في السلة بنفس الطريقة التي يبحث بها ملف cart-api.php
        $checkCart = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ? AND variation_id = ? AND attributes = ?");
        $checkCart->execute([$user_id, (int)$product_id, $v_id, $attr]);
        $existing = $checkCart->fetch();

        if ($existing) {
            // إذا وجده، يزيد الكمية فقط (تحديث)
            $new_qty = $existing['quantity'] + 1;
            $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ?")->execute([$new_qty, $existing['id']]);
        } else {
            // إذا لم يجده، ينشئ سطراً جديداً (إضافة) بنفس القيم الصارمة
            $pdo->prepare("INSERT INTO user_cart (user_id, product_id, variation_id, quantity, attributes) VALUES (?, ?, ?, 1, ?)")
                ->execute([$user_id, (int)$product_id, $v_id, $attr]);
        }
    }

    // 3. حذف المنتجات من المفضلة بعد النقل الناجح
    $pdo->prepare("DELETE FROM user_wishlist WHERE user_id = ?")->execute([$user_id]);

    $pdo->commit();
    
    // مسح كاش الجلسة لضمان تحديث الأرقام فوراً
    unset($_SESSION['wishlist_cache']);
    unset($_SESSION['cart_cache']);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}