<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
require_once 'db.php';
require_once 'functions.php';

// حماية صارمة: إذا لم يكن هناك جلسة، أرسل خطأ فوراً
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'need_login', 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}
// ... باقي الكود كما هو
// 2. حماية Brute Force (منع الروبوتات من التلاعب بالمفضلة)
// يسمح بـ 10 عمليات كل 30 ثانية فقط
if (!isset($_SESSION['wishlist_limit'])) {
    $_SESSION['wishlist_limit'] = ['count' => 0, 'time' => time()];
}
if (time() - $_SESSION['wishlist_limit']['time'] < 30) {
    if ($_SESSION['wishlist_limit']['count'] > 10) {
        echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please wait.']);
        exit;
    }
    $_SESSION['wishlist_limit']['count']++;
} else {
    $_SESSION['wishlist_limit'] = ['count' => 1, 'time' => time()];
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id > 0) {
    try {
        // التحقق من وجود المنتج مسبقاً
        $stmt = $pdo->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->fetch()) {
            // حذف من المفضلة
            $pdo->prepare("DELETE FROM user_wishlist WHERE user_id = ? AND product_id = ?")->execute([$user_id, $product_id]);
            $action = 'removed';
        } else {
            // إضافة للمفضلة
            $pdo->prepare("INSERT INTO user_wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
            $action = 'added';
        }
   // --- التحديث المطلوب هنا ---
        // مسح الكاش فوراً بعد الإضافة أو الحذف لكي يضطر الهيدر لجلب البيانات الجديدة
        unset($_SESSION['wishlist_cache']); 
        // ---------------------------
        // جلب العدد الإجمالي المحدث (لعرضه في البادج فوراً)
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_wishlist WHERE user_id = ?");
        $countStmt->execute([$user_id]);
        $newCount = $countStmt->fetchColumn();

        // تحديث الكاش المحلي للمستخدم لضمان السرعة في الهيدر
        if (function_exists('set_user_cache')) {
            set_user_cache('wishlist_count', $newCount);
        }

        echo json_encode([
            'status' => 'success', 
            'action' => $action, 
            'count' => $newCount
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
}