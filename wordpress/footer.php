<?php 
require_once 'db.php';        
require_once 'functions.php'; 

// تحميل متغيرات البيئة (اختياري حسب إعدادك)
if (file_exists(__DIR__ . '/keys.env')) {
    $lines = file(__DIR__ . '/keys.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1], " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// فحص حالة الاشتراك المسبق للـ IP
if(isset($pdo) && !isset($_SESSION['has_subscribed'])){
    try {
        $ip_check = getClientIP();
        $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE ip_address = ? LIMIT 1");
        $stmt->execute([$ip_check]);
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
    .footer-link:hover { color: #C8A95A; }
    @media (min-width: 768px) { .footer-link:hover { transform: translateX(-5px); } }
    .footer-input { background-color: transparent; border: none; border-bottom: 1px solid #4b5563; color: white; width: 100%; max-width: 300px; padding: 10px 0; outline: none; transition: border-color 0.3s ease; text-align: center; }
    .footer-input:focus { border-color: #C8A95A; }
    .footer-input::placeholder { color: #6b7280; }
    @media (min-width: 768px) { .footer-input { text-align: right; } }
    .btn-footer { font-family: 'Cairo', sans-serif; background-color: transparent; border: 1px solid #ffffff; color: #ffffff; padding: 0.6rem 1.5rem; font-weight: bold; transition: all 0.3s ease; cursor: pointer; margin-top: 1rem; font-size: 0.9rem; }
    @media (hover: hover) { .btn-footer:hover { border-color: #C8A95A; color: #C8A95A; background-color: rgba(200, 169, 90, 0.1); } }
    .studio-link { color: #C8A95A; text-decoration: none; font-weight: bold; transition: all 0.3s ease; }
    .studio-link:hover { text-decoration: underline; }
</style>

<footer class="bg-black-footer text-white pt-16 pb-8 border-t border-gray-900" dir="rtl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12 text-center md:text-right">
            
            <div class="flex flex-col items-center md:items-start space-y-4">
                <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">نبذة عنا</h4>
                <p class="font-cairo text-gray-400 text-sm leading-relaxed max-w-xs mx-auto md:mx-0">
                    نقدم أرقى العُطُور والإكسسوارات الفاخرة التي تعكس ذوقك الرفيع. الجودة والأصالة هما عنواننا الدائم.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-start space-y-4">
                <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">روابط سريعة</h4>
                <ul class="space-y-2 font-cairo text-sm w-full">
                    <li><a href="index.php?categurie=index" class="footer-link">الرئيسية</a></li>
                    <li><a href="filter.php?categurie=filter" class="footer-link">منتجاتنا</a></li>
                    <li><a href="about-us.html" class="footer-link">من نحن</a></li>
                </ul>
            </div>

            <div class="flex flex-col items-center md:items-start space-y-4">
                <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">المساعدة والسياسات</h4>
                <ul class="space-y-2 font-cairo text-sm w-full">
                    <li><a href="privacy-policy.html" class="footer-link">سياسة الخصوصية</a></li>
                    <li><a href="contact-us.html" class="footer-link">الأسئلة الشائعة</a></li>
                </ul>
            </div>

            <div class="flex flex-col items-center md:items-start space-y-4">
                <h4 class="text-lg font-bold font-cairo text-gold mb-2 md:border-r-4 md:border-gold md:pr-3">النشرة البريدية</h4>
                <p class="font-cairo text-gray-400 text-sm max-w-xs mx-auto md:mx-0">اشترك الآن للحصول على آخر العروض والأخبار الحصرية.</p>
                
                <form id="subscribeForm" class="flex flex-col items-center md:items-start w-full">
                    <!-- حقل التوكن المخفي -->
                    <input type="hidden" name="csrf_token" id="footer_csrf" value="<?php echo $csrf_token; ?>">
                    
                    <div style="display: none;"><input type="text" name="website_trap"></div>

                    <input type="email" id="sub_email" name="email" placeholder="أدخل بريدك الإلكتروني" 
                           class="footer-input font-cairo mb-2" required>
                    
                    <div class="cf-turnstile" 
                         data-sitekey="0x4AAAAAAB6EwGuBkcNho5N1" 
                         data-size="invisible" 
                         data-callback="onTurnstileSuccess"></div>

                    <button type="submit" id="sub_btn" class="btn-footer w-full md:w-auto">
                        <span id="btnText">اشترك</span>
                    </button>
                    <div id="sub_msg" class="text-sm mt-2 font-cairo"></div>
                </form>
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
let manualTrigger = false;

function onTurnstileSuccess(token) {
    if (!manualTrigger) return;

    const btnText = document.getElementById('btnText');
    const form = document.getElementById('subscribeForm');
    const msgDiv = document.getElementById('sub_msg');
    const csrfInput = document.getElementById('footer_csrf');
    const emailInput = document.getElementById('sub_email');
    
    btnText.innerText = 'جاري المعالجة...';
    
    const formData = new FormData(form);

    fetch('subscribe_process.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        // تحديث التوكن فوراً لأي عملية قادمة لمنع Session Expired
        if (data.new_token) {
            csrfInput.value = data.new_token;
        }

        if (data.status === 'success') {
            msgDiv.style.color = '#C8A95A';
            msgDiv.innerText = data.message;
            emailInput.value = ''; // مسح الحقل للنجاح
        } else {
            msgDiv.style.color = '#ef4444';
            msgDiv.innerText = data.message;
        }
        
        // إعادة تهيئة الزر والكابتشا فوراً
        resetFooterBtn();
    })
    .catch(error => {
        msgDiv.style.color = '#ef4444';
        msgDiv.innerText = 'حدث خطأ في الاتصال، حاول مجدداً.';
        resetFooterBtn();
    });
}

function resetFooterBtn() {
    manualTrigger = false; 
    const btn = document.getElementById('sub_btn');
    const btnText = document.getElementById('btnText');
    btn.disabled = false;
    btnText.innerText = 'اشترك';
    // تصفير الكابتشا إلزامي لكي تعمل المحاولة الثانية
    if (typeof turnstile !== 'undefined') {
        turnstile.reset(); 
    }
}

document.getElementById('subscribeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    manualTrigger = true; 
    
    const btn = document.getElementById('sub_btn');
    const btnText = document.getElementById('btnText');
    
    btn.disabled = true;
    btnText.innerText = 'جاري التحقق...';

    if (typeof turnstile !== 'undefined') {
        turnstile.execute(); // استدعاء الكابتشا يدوياً
    } else {
        resetFooterBtn();
        alert('حدث خطأ في نظام الحماية، يرجى تحديث الصفحة.');
    }
});
</script>