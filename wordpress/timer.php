<?php
// 1. مصفوفة النصوص العشوائية للعنوان
$timer_headlines = [
    'عرض حصري لفترة محدودة – ينتهي خلال:',
    'كمية محدودة، اطلب الآن قبل نفاد العرض:',
    'الفرصة الأخيرة للاستفادة من هذا العرض:',
    'تخفيضات مؤقتة متاحة لفترة قصيرة:'
];

// اختيار نص عشوائي
$random_headline = $timer_headlines[array_rand($timer_headlines)];

// التأكد من وجود ID المنتج
$current_p_id = isset($product_id) ? $product_id : 'global';
?>

<style>
    /* حاوية المؤقت - مخفية في البداية لمنع ظهور الأصفار */
    .timer-wrapper {
        background: transparent;
        border-radius: 8px;
        padding: 15px;
        margin: 20px auto; 
        text-align: center;
        width: 100%;
        max-width: 420px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-top: none;
        
        /* هذا هو حل مشكلة الوميض */
        opacity: 0; 
        transition: opacity 0.5s ease-in-out;
        visibility: hidden; /* لمنع حجز مكان فارغ بشكل غريب */
    }

    /* كلاس يتم إضافته بالجافاسكربت لإظهار المؤقت */
    .timer-wrapper.loaded {
        opacity: 1;
        visibility: visible;
    }

    .timer-headline {
        font-family: 'Cairo', sans-serif;
        color: #dc2626;
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }

    .fire-icon {
        color: #dc2626;
        font-size: 1.2rem;
        display: inline-block;
        animation: fireShake 0.4s infinite ease-in-out;
    }

    @keyframes fireShake {
        0% { transform: translateX(0) rotate(0deg); }
        25% { transform: translateX(-2px) rotate(-5deg); }
        50% { transform: translateX(0) rotate(0deg); }
        75% { transform: translateX(2px) rotate(5deg); }
        100% { transform: translateX(0) rotate(0deg); }
    }

    .timer-grid {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        direction: ltr; 
        width: 100%;
    }

    .time-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 8px 5px;
        width: 75px;
        border-radius: 6px;
        border: 1px solid #eee;
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    .time-number {
        font-family: 'Arial', sans-serif;
        font-weight: 800;
        font-size: 1.8rem;
        color: #000;
        line-height: 1.1;
    }

    .time-label {
        font-family: 'Tajawal', sans-serif;
        font-size: 0.8rem;
        color: #555;
        font-weight: 700;
        margin-top: 3px;
    }

    .separator {
        font-size: 1.5rem;
        font-weight: 800;
        color: #C8A95A;
        margin-bottom: 18px; 
    }

    @media (max-width: 768px) {
        .timer-wrapper {
            margin-top: 0 !important;
            margin-bottom: 20px;
            border-width: 1px;
            padding: 10px;
        }
        .time-block {
            width: 65px;
            padding: 5px;
        }
        .time-number {
            font-size: 1.5rem;
        }
        .timer-headline {
            font-size: 0.95rem;
        }
    }
</style>

<div class="timer-wrapper" id="timerBox">
    <!-- العنوان العشوائي -->
    <div class="timer-headline">
        <i class="fa-solid fa-fire fire-icon"></i>
        <span><?php echo $random_headline; ?></span>
    </div>
    
    <div class="timer-grid">
        <!-- الساعات -->
        <div class="time-block">
            <span class="time-number" id="t-hours">--</span>
            <span class="time-label">ساعات</span>
        </div>

        <div class="separator">:</div>

        <!-- الدقائق -->
        <div class="time-block">
            <span class="time-number" id="t-minutes">--</span>
            <span class="time-label">دقائق</span>
        </div>

        <div class="separator">:</div>

        <!-- الثواني -->
        <div class="time-block">
            <span class="time-number" id="t-seconds">--</span>
            <span class="time-label">ثواني</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. استلام ID المنتج من PHP ليكون المفتاح فريداً لكل منتج
    const productId = "<?php echo $current_p_id; ?>";
    const storageKey = 'unique_timer_v2_' + productId; // غيرنا المفتاح لضمان إعادة تعيين الكاش القديم
    
    // العنصر الرئيسي للمؤقت
    const timerBox = document.getElementById('timerBox');

    // 2. إعدادات الوقت العشوائي (بين 2 إلى 6 ساعات)
    const minHours = 1;
    const maxHours = 2;

    // دالة لجلب أو إنشاء وقت الانتهاء
    function getEndTime() {
        let savedTime = localStorage.getItem(storageKey);
        
        // إذا وجدنا وقتاً محفوظاً، نتأكد أنه لم ينتهِ بعد
        if (savedTime) {
            let parsedTime = new Date(savedTime);
            // إذا كان الوقت المحفوظ في المستقبل، نستخدمه
            if (parsedTime > new Date()) {
                return parsedTime;
            }
        }

        // إذا لم يوجد وقت أو انتهى الوقت القديم، ننشئ وقتاً جديداً
        const now = new Date();
        
        // توليد أرقام عشوائية لتبدو حقيقية
        const randomHours = Math.floor(Math.random() * (maxHours - minHours + 1) + minHours);
        const randomMinutes = Math.floor(Math.random() * 59);
        const randomSeconds = Math.floor(Math.random() * 59);

        now.setHours(now.getHours() + randomHours);
        now.setMinutes(now.getMinutes() + randomMinutes);
        now.setSeconds(now.getSeconds() + randomSeconds);

        localStorage.setItem(storageKey, now);
        return now;
    }

    let endTime = getEndTime();
    let firstRun = true;

    function updateTimer() {
        const now = new Date();
        const difference = endTime - now;

        // إذا انتهى الوقت والعميل فاتح للصفحة، نعيد تعيينه لوقت قصير (مثلاً 45 دقيقة) للحفاظ على الاستعجال
        if (difference <= 0) {
            localStorage.removeItem(storageKey);
            // إنشاء وقت جديد قصير (45 دقيقة)
            const shortTime = new Date();
            shortTime.setMinutes(shortTime.getMinutes() + 45);
            localStorage.setItem(storageKey, shortTime);
            endTime = shortTime;
            return;
        }

        const hours = Math.floor((difference / (1000 * 60 * 60)) % 24);
        const minutes = Math.floor((difference / 1000 / 60) % 60);
        const seconds = Math.floor((difference / 1000) % 60);

        // تحديث الأرقام في HTML
        document.getElementById('t-hours').innerText = hours < 10 ? '0' + hours : hours;
        document.getElementById('t-minutes').innerText = minutes < 10 ? '0' + minutes : minutes;
        document.getElementById('t-seconds').innerText = seconds < 10 ? '0' + seconds : seconds;

        // بعد أول تحديث للأرقام، نظهر المؤقت تدريجياً
        if (firstRun) {
            // نستخدم requestAnimationFrame لضمان سلاسة الحركة
            requestAnimationFrame(() => {
                timerBox.classList.add('loaded');
            });
            firstRun = false;
        }
    }

    // تشغيل الدالة فوراً لتحديث الأرقام قبل إظهارها
    updateTimer();
    
    // تحديث كل ثانية
    setInterval(updateTimer, 1000);
});
</script>