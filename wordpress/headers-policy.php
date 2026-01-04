<?php
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
if (!isset($_SERVER['HTTP_HOST']) || strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '0'); // ساعد في التصحيح على localhost
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
}
?>