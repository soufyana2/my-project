<?php
ob_start();
//require_once 'db.php';
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي | Abdelwahab Accessories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        :root { --ms-gold: #C8A95A; --ms-black: #000000; }
        body { background-color: #ffffff !important; font-family: 'Cairo', sans-serif !important; margin: 0; direction: rtl !important; }

        .ms-profile-card {
            border: 0 !important; border-radius: 0 !important;
            box-shadow: 0 15px 50px rgba(0,0,0,0.08);
            width: 100%; max-width: 500px; background: #fff;
            margin: 90px auto 50px; opacity: 0;
            transform: translateY(10px); transition: opacity 0.4s ease-out, transform 0.4s ease-out;
        }
        .ms-profile-card.visible { opacity: 1; transform: translateY(0); }

        @media (min-width: 1024px) { .ms-profile-card { margin-top: 160px; } }

        /* دائرة البروفايل مع المنطق القديم */
        .ms-profile-circle {
            width: 110px; height: 110px; border-radius: 50%;
            border: 4px solid var(--ms-gold) !important;
            background-color: purple; /* لون الخلفية للمسجلين */
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 25px; color: #ffffff !important;
            font-weight: 900; font-size: 2.8rem;
            background-repeat: no-repeat; background-position: center; background-size: 65%;
        }

        .ms-data-row { display: flex; flex-direction: column; text-align: right; border-bottom: 1px solid #f8f8f8; padding-bottom: 8px; margin-bottom: 15px; }
        .ms-data-label { font-size: 0.75rem; color: #aaa; }
        .ms-data-value { font-weight: 700; color: #000; }

        .ms-bio-alert { background-color: #fdfaf3; border-right: 4px solid var(--ms-gold); padding: 15px; text-align: right; font-size: 0.85rem; color: #555; margin-bottom: 25px; }

        .ms-whatsapp-btn {
            background-color: #25D366 !important; color: #fff !important;
            width: 100%; padding: 16px; text-align: center; font-weight: 900;
            display: block; border-radius: 0 !important; text-decoration: none;
            box-shadow: none !important;
        }

        .ms-action-btn {
            background: transparent; color: #000; border: 1px solid #000;
            padding: 14px; font-weight: bold; text-align: center;
            width: 100%; display: block; border-radius: 0 !important;
            margin-top: 10px; transition: all 0.3s ease; cursor: pointer;
        }

        @media (min-width: 1024px) {
            .ms-whatsapp-btn:hover { background-color: #128C7E !important; }
            .ms-action-btn:hover { color: var(--ms-gold) !important; border-color: var(--ms-gold) !important; }
        }
    </style>
</head>

<body>

    <?php 
    include 'header.php'; 
    $isLoggedIn = isset($_SESSION['user_id']);
    $userName = $_SESSION['username'] ?? 'Guest User';
    $firstLetter = strtoupper(substr($userName, 0, 1));
    $whatsapp_number = $_ENV['whatsapp_number'] ?? '212634229259';
    $logo_url = "https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png";
    ?>

    <main class="min-h-screen flex items-center justify-center">
        <div id="profileCard" class="ms-profile-card">
            <div class="p-8 md:p-12">
                
                <!-- منطق دائرة البروفايل المعدل -->
                <div class="ms-profile-circle" 
                    <?php if(!$isLoggedIn) echo "style='background-image: url(\"$logo_url\"); background-color: #fff;'"; ?>>
                    <?php if($isLoggedIn): ?>
                        <span style="color: #ffffff !important;"><?php echo $firstLetter; ?></span>
                    <?php endif; ?>
                </div>

                <div class="text-center mb-8">
                    <h1 class="text-xl font-black text-black uppercase tracking-widest"><?php echo $userName; ?></h1>
                </div>

                <div class="ms-bio-alert">
                    <i class="fas fa-user-shield ml-2"></i>
                    جميع هذه البيانات تُستخدم فقط لتأمين طلباتك الخاصة.
                </div>

                <div class="mb-8">
                    <div class="ms-data-row">
                        <span class="ms-data-label">الدولة / المدينة</span>
                        <span class="ms-data-value">المغرب / الدار البيضاء</span>
                    </div>
                    <div class="ms-data-row">
                        <span class="ms-data-label">العنوان الكامل</span>
                        <span class="ms-data-value">شارع الزرقطوني، الدار البيضاء</span>
                    </div>
                    <div class="ms-data-row">
                        <span class="ms-data-label">رقم الهاتف</span>
                        <span class="ms-data-value" dir="ltr">+212 600-000000</span>
                    </div>
                </div>

                <!-- زر الواتساب -->
                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" class="ms-whatsapp-btn">
                    <i class="fab fa-whatsapp ml-2 text-xl"></i> ANY QUESTION AT WHATSAPP
                </a>
                <?php if ($isLoggedIn): ?>

                <!-- زر الإعدادات أسفل الواتساب -->
                <button onclick="openSettingsMenu()" class="ms-action-btn">
                    <i class="fas fa-cog ml-2"></i> إعدادات الحساب
                </button>

                <div class="mt-4 space-y-2">
                    <button class="ms-action-btn">RESET PASSWORD</button>
                    <button class="ms-action-btn text-red-600 border-red-200">DELETE ACCOUNT</button>
                </div>
                <?php endif; ?>

            </div>

            <div class="flex h-1.5 w-full">
                <div class="flex-1 bg-black"></div>
                <div class="flex-1 bg-[#C8A95A]"></div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <div id="settingsOverlay" class="fixed inset-0 bg-black/40 z-[998] hidden opacity-0 transition-opacity duration-300" onclick="closeSettingsMenu()"></div>

    <div id="settingsSidebar" class="fixed top-0 right-0 h-full w-[320px] bg-white z-[999] shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
        <div class="p-6 border-b flex justify-between items-center bg-black text-white">
            <h2 class="font-bold text-lg uppercase tracking-wider">إعدادات الحساب</h2>
            <i class="fas fa-times cursor-pointer text-xl hover:text-[#C8A95A]" onclick="closeSettingsMenu()"></i>
        </div>

        <div class="p-6 space-y-6 overflow-y-auto flex-1">
            
            <div class="flex flex-col">
                <!-- تم تحسين اللون هنا إلى الأسود الغامق -->
                <label class="text-xs text-black font-bold mb-2 uppercase tracking-tight">الدولة</label>
                <select class="border border-black p-3 font-bold text-sm outline-none focus:border-[#C8A95A]">
                    <option value="MA">المغرب</option>
                    <option value="SA">السعودية</option>
                    <option value="AE">الإمارات</option>
                </select>
            </div>

            <div class="flex flex-col">
                <label class="text-xs text-black font-bold mb-2 uppercase tracking-tight">المدينة</label>
                <select class="border border-black p-3 font-bold text-sm outline-none focus:border-[#C8A95A]">
                    <option>الدار البيضاء</option>
                    <option>الرباط</option>
                </select>
            </div>

            <div class="flex flex-col">
                <label class="text-xs text-black font-bold mb-2 uppercase tracking-tight">تغيير العنوان</label>
                <input type="text" placeholder="أدخل العنوان الكامل" class="border border-black p-3 text-sm outline-none focus:border-[#C8A95A]">
            </div>

            <div class="flex flex-col">
                <label class="text-xs text-black font-bold mb-2 uppercase tracking-tight">رقم الهاتف</label>
                <input type="tel" placeholder="+212 600-000000" class="border border-black p-3 text-sm outline-none focus:border-[#C8A95A]" dir="ltr">
            </div>

            <button class="ms-action-btn bg-black text-white hover:bg-[#C8A95A] !border-black">حفظ التغييرات</button>
        </div>
    </div>

    <script>
        function openSettingsMenu() {
            const sidebar = document.getElementById('settingsSidebar');
            const overlay = document.getElementById('settingsOverlay');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.add('opacity-100');
                sidebar.classList.remove('translate-x-full');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeSettingsMenu() {
            const sidebar = document.getElementById('settingsSidebar');
            const overlay = document.getElementById('settingsOverlay');
            sidebar.classList.add('translate-x-full');
            overlay.classList.remove('opacity-100');
            setTimeout(() => {
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const card = document.getElementById('profileCard');
            if(card) card.classList.add('visible');
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>