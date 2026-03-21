<?php
require_once __DIR__ . '/store_brand.php';
store_brand_enable_auto_replace();
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="public/images/favicon.svg">
  <title>طلب غير صالح - CSRF Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-red-500 py-6 px-6 text-center">
            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">طلب غير صالح</h1>
        </div>

        <!-- Content -->
        <div class="p-8 text-center">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">خطأ في المصادقة (CSRF)</h2>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-right">
                <p class="text-yellow-800 text-sm leading-relaxed">
                    <span class="font-bold">السبب المحتمل:</span><br>
                    • انتهت صلاحية الجلسة<br>
                    • محاولة وصول غير مصرح بها<br>
                    • مشكلة في التخزين المؤقت للمتصفح
                </p>
            </div>

            <div class="space-y-3">
                <a href="register.php" 
                   class="block w-full bg-blue-600 lg:hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-200 transform lg:hover:scale-105 shadow-md">
                    ↶ العودة إلى صفحة التسجيل
                </a>
                
                <a href="javascript:location.reload()" 
                   class="block w-full bg-gray-600 lg:hover:bg-gray-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-200 shadow-md">
                    🔄 إعادة تحميل الصفحة
                </a>
                
                <button onclick="clearCacheAndReload()" 
                   class="block w-full bg-orange-500 lg:hover:bg-orange-600 text-white py-3 px-6 rounded-lg font-medium transition-all duration-200 shadow-md">
                    🧹 مسح الذاكرة وإعادة التحميل
                </button>
            </div>

            <!-- Technical Details (for debugging) -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <details class="text-right">
                    <summary class="text-sm text-gray-600 cursor-pointer lg:hover:text-gray-800">
                        التفاصيل التقنية (للمطورين)
                    </summary>
                    <div class="mt-2 p-3 bg-gray-100 rounded-lg text-xs text-gray-700 text-right dir-ltr">
                        <div><strong>Error:</strong> CSRF Token Validation Failed</div>
                        <div><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></div>
                        <div><strong>IP:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></div>
                        <div><strong>User Agent:</strong> <?php echo substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 50); ?>...</div>
                    </div>
                </details>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-100 py-4 px-6 text-center">
            <p class="text-xs text-gray-600">
                إذا استمرت المشكلة، يرجى <a href="#" class="text-blue-600 lg:hover:underline">الاتصال بالدعم الفني</a>
            </p>
        </div>
    </div>

    <script>
  
        // إضافة تأثيرات عند التحميل
        document.addEventListener('DOMContentLoaded', function() {
            const mainDiv = document.querySelector('.max-w-md');
            mainDiv.style.opacity = '0';
            mainDiv.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                mainDiv.style.transition = 'all 0.5s ease';
                mainDiv.style.opacity = '1';
                mainDiv.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
