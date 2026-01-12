<?php
use Dotenv\Dotenv;
require_once 'db.php';
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
// 2. تحميل ملف apikeys.env (نحتاجها لتعريف WooCommerce Client)
try {
    if (file_exists(__DIR__ . '/apikeys.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__, 'apikeys.env');
        $dotenv->load();
    }
} catch (Exception $e) { /* تجاهل الخطأ إذا كان محملاً مسبقاً */ }

require_once 'functions.php'; // هذا الملف يحتوي على secure_session_start() التي تشغل الـ Remember me
manage_csrf_token();
$whatsapp_number = $_ENV['whatsapp_number'] ?? '212000000000';

// الآن بعد تشغيل الوظائف، نتحقق من تسجيل الدخول
$isLoggedIn = isset($_SESSION['user_id']);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}


// 3. تعريف الروابط النشطة (كودك الحالي)
$currentScript = basename($_SERVER['PHP_SELF']);
$urlCategory = isset($_GET['categurie']) ? $_GET['categurie'] : '';
$activeCategory = ''; 

if ($currentScript == 'index.php') { $activeCategory = 'index'; } 
elseif ($currentScript == 'filter.php') {
    if (empty($urlCategory)) { $activeCategory = 'filter'; } 
    else {
        if ($urlCategory == 'عطور') $activeCategory = 'parfums';
        elseif ($urlCategory == 'الإكسسوارات') $activeCategory = 'accessories';
        elseif ($urlCategory == 'ساعات') $activeCategory = 'watches';
        elseif ($urlCategory == 'باقات وعروض') $activeCategory = 'pack';
    }
}

// 4. منطق المفضلة (Wishlist) المطور - نظام الكاش السريع
$wishlistProductsData = [];
$wishlistCount = 0;

if ($isLoggedIn) {
    // جلب الـ IDs من قاعدة البيانات (دائماً سريعة)
    $stmt = $pdo->prepare("SELECT product_id FROM user_wishlist WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlistIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $wishlistCount = count($wishlistIds);

    if ($wishlistCount > 0) {
        // إذا كانت البيانات موجودة في الجلسة ولدينا نفس العدد، نستخدمها فوراً
        if (isset($_SESSION['wishlist_cache']) && count($_SESSION['wishlist_cache']) == $wishlistCount) {
            $wishlistProductsData = $_SESSION['wishlist_cache'];
        } else {
            try {
                if (!isset($woocommerce)) {
                    $woocommerce = new Automattic\WooCommerce\Client(
                        $_ENV['wordpress_url'], $_ENV['consumer_key'], $_ENV['secret_key'],
                        ['version' => 'wc/v3', 'verify_ssl' => false, 'timeout' => 10]
                    );
                }
                $wishlistProductsData = $woocommerce->get('products', ['include' => $wishlistIds]);
                $wishlistProductsData = json_decode(json_encode($wishlistProductsData), true);
                $_SESSION['wishlist_cache'] = $wishlistProductsData; // تخزين في الكاش
            } catch (Exception $e) { $wishlistProductsData = []; }
        }
    }
}
// --- أضف هذا بجانب منطق المفضلة في أعلى الملف ---
$cartCount = 0;
if ($isLoggedIn) {
    // حساب عدد المنتجات في السلة للمسجلين
    $stmtCart = $pdo->prepare("SELECT SUM(quantity) FROM user_cart WHERE user_id = ?");
    $stmtCart->execute([$_SESSION['user_id']]);
    $cartCount = (int)$stmtCart->fetchColumn();
} else {
    // حساب عدد المنتجات لزوار الموقع (Guest)
    if (isset($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $item) {
            $cartCount += $item['quantity'];
        }
    }
}
?>
    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#0f172a',
                            light: '#1e293b',
                            dark: '#020617'
                        },
                        accent: {
                            DEFAULT: '#C8A95A',
                            light: '#d4b882',
                            dark: '#b8965a'
                        },
                        surface: {
                            DEFAULT: '#ffffff',
                            elevated: '#f8fafc',
                            hover: '#f1f5f9'
                        },
                        border: {
                            DEFAULT: '#e2e8f0',
                            light: '#f1f5f9'
                        },
                        text: {
                            primary: '#0f172a',
                            secondary: '#475569',
                            muted: '#64748b'
                        }
                    },
                    fontFamily: {
                            sans: ['Cairo', 'sans-serif'],
                            serif: ['Tajawal', 'serif'] 
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-down': 'slideDown 0.3s ease-out',
                        'scale-in': 'scaleIn 0.2s ease-out',
                        'shimmer': 'shimmer 2s linear infinite',
                        'search-slide-down': 'searchSlideDown 0.3s ease-out',
                        'search-slide-up': 'searchSlideUp 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideDown: {
                            '0%': { transform: 'translateY(-10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.95)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' }
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' }
                        },
                        searchSlideDown: {
                            '0%': { transform: 'translateY(-100%)' },
                            '100%': { transform: 'translateY(0)' }
                        },
                        searchSlideUp: {
                            '0%': { transform: 'translateY(0)' },
                            '100%': { transform: 'translateY(-100%)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --primary: #1a1a1a;
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --text-primary: #1a1a1a;
            --text-secondary: #64748b;
            --accent: #d4af37;
            --border: #e2e8f0;
        }

        [data-theme="dark"] {
            --primary: #ffffff;
            --surface: #1a1a1a;
            --surface-hover: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --accent: #d4af37;
            --border: #374151;
        }


/* تعريف الدوران بشكل صريح */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.wishlist-spinner-circle {
    width: 40px !important;
    height: 40px !important;
    border: 4px solid #f3f3f3 !important; /* اللون الباهت */
    border-top: 4px solid #C8A95A !important; /* اللون الذهبي المتحرك */
    border-radius: 50% !important;
    display: inline-block !important; /* لضمان قبول التحريك */
    
    /* تفعيل الانيميشن قسرياً */
    animation: spin 0.8s linear infinite !important;
    -webkit-animation: spin 0.8s linear infinite !important;
}
    [x-cloak] { display: none !important; }
/* هذا السطر يمنع ظهور العناصر قبل تحميل التنسيقات */
.header-modern { opacity: 0; transition: opacity 0.2s ease-in; animation: fadeIn 0.3s ease-in forwards; }
.header-loaded .header-modern { opacity: 1; }

@keyframes fadeIn { to { opacity: 1; } }
/* إعدادات الأيقونات */
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    vertical-align: middle; /* لمحاذاة الأيقونة مع النص */
}
/* تعديل الهوامش للعربية (Flip Margins) */
.mr-2 { margin-left: 0.5rem; margin-right: 0; } /* عكس اليمين لليسار */
.ml-2 { margin-right: 0.5rem; margin-left: 0; }
        /* ===== HEADER STYLES ===== */
        .header-modern {
background-color: #ffffff; 
            /* font-family: 'Cairo', 'sans-serif';*/
             backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
            direction: rtl;
            transition: transform 0.4s ease-in-out; /* هذا السطر يضيف حركة الانزلاق الناعمة */
        }
/* تثبيت تصميم عنوان المنتج وتصنيفه في جميع الصفحات */
.product-card-professional h4 {
    color: #000000 !important; /* اللون أسود تابت */
    font-family: 'Cairo', sans-serif !important; /* الخط المعتمد */
    font-size: 14px !important; /* الحجم المناسب للعنوان */
    font-weight: 700 !important;
    line-height: 1.4 !important;
}

.product-card-professional .category-text, 
.product-card-professional p.text-xs {
    color: #C8A95A !important; /* اللون الذهبي للتصنيف */
    font-family: 'Cairo', sans-serif !important;
    font-size: 11px !important; /* حجم التصنيف */
    font-weight: 600 !important;
}
/* ضمان ظهور أيقونة الحذف بشكل صحيح */
.remove-product-icon.active {
    color: #ef4444 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

        /* ===== BUTTON STYLES ===== */
        .icon-button {
            position: relative;
            background: transparent;
            border: none;
            transition: color 0.3s ease;
            color: black !important;
            cursor: pointer;
        }

        .btn-professional {
            background: linear-gradient(135deg, #C8A95A, #d4b882);
            border: 1px solid rgba(200, 169, 90, 0.3);
            box-shadow: 0 4px 15px rgba(200, 169, 90, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-whatsappp {
            background-color: #25D366;
            border: none;
 transition: 0.2s;  
 font-weight: bold;
      }

        @media (min-width: 1024px) {
            .icon-button:hover {
                color: #C8A95A;
            }

            .btn-professional:hover {
                background: linear-gradient(135deg, #d4b882, #C8A95A);
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(200, 169, 90, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }

            .btn-whatsappp:hover {
               background-color: #128C7E;
            }
        }

        /* ===== BADGE STYLES ===== */
       .cart-badge,
.wishlist-badge {
    position: absolute;
    top: -4px;        /* تم التعديل لتقريبها من الأيقونة */
    right: -4px;      /* تم التعديل لتكون في الزاوية تماماً */
    left: auto;       /* إلغاء left */
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border-radius: 50% !important;
    width: 16px;      /* تم التصغير من 20px */
    height: 16px;     /* تم التصغير من 20px */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;   /* تصغير الخط */
    font-weight: 700;
    z-index: 20;
}

        /* ===== NAVIGATION STYLES ===== */
        .nav-menu {
background-color: #ffffff !important;             
border-bottom: 1px solid #f1f5f9 !important;
        }

    /* هذا الجزء يضمن أن الروابط ستكون بنفس الحجم والخط في كل الصفحات */
.nav-link {
    padding: 12px 20px;
    color: black !important; /* لون النص */
    text-decoration: none;
    /* التعديل هنا: نثبت الخط والوزن ليتطابق مع صفحة الفلتر */
    font-family: 'Cairo', sans-serif !important; 
    font-weight: 700 !important; /* جعل الخط عريض وواضح */
    font-size: 14px !important;  /* حجم الخط المناسب */
    letter-spacing: 0px !important;
    text-transform: none !important; /* إلغاء الحروف الكبيرة إذا كانت موجودة */
    transition: all 0.3s ease;
    position: relative;
    margin: 0 4px;
    display: flex;
    align-items: center;
}

        .nav-link.active {
            color: #C8A95A!important;
            background: rgba(200, 169, 90, 0.1)!important ;
        }

        .nav-link::after {
            content: '';
            position: absolute!important;
            bottom: -1px!important;
            left: 50%!important;
            transform: translateX(-50%)!important;
            width: 0!important;
            height: 2px!important;
            background: #C8A95A !important;
            transition: width 0.3s ease !important;
        }

        .nav-link.active::after {
            width: 60%!important;
        }

        @media (min-width: 1024px) {
            .nav-link:hover {
                color: #C8A95A !important;
                background: rgba(200, 169, 90, 0.05) !important;
            }

            .nav-link:hover::after {
                width: 60%!important;
            }
        }

        /* ===== LOGO STYLES ===== */
        .logo-text {
            font-family: 'Playfair Display', serif!important;
            font-weight: 700!important;
            font-size: 22px!important;
            color: #0f172a!important;
            letter-spacing: 1px!important;
        }

        .logo-subtitle {
            font-size: 9px!important;
            color: #64748b!important;
            font-weight: 500!important;
            letter-spacing: 2px!important;
            margin-top: -2px!important;
                        font-family: 'Playfair Display', serif!important;

        }

        .gradient-text {
            background: linear-gradient(135deg, #0f172a, #1e293b, #C8A95A)!important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent!important;
            background-clip: text!important;
                        font-family: 'Playfair Display', serif!important;

        }

        /* ===== MOBILE MENU STYLES ===== */
       .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh; 
            height: 100dvh; /* هذا السطر مهم جداً للموبايل */
            background: #ffffff !important;
            backdrop-filter: blur(20px);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
            font-family: 'Cairo', 'sans-serif' !important;
            /* التعديلات الخاصة بالتمرير */
            overflow-y: auto !important; /* إجبار التمرير العمودي */
            -webkit-overflow-scrolling: touch !important; /* نعومة اللمس */
            padding-bottom: 90px !important; /* مسافة كبيرة في الأسفل لرفع آخر رابط (ساعات) للأعلى */
        }

        .mobile-menu.active {
            transform: translateX(0);
        }

     .mobile-nav-link {
            display: block;
            padding: 16px 20px;
            
            color: #000000 !important; /* تم تغيير اللون لأسود كما طلبت */
            
            text-decoration: none;
            font-weight: 700; /* جعل الخط أعرض قليلاً ليكون أوضح */
            font-size: 15px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        @media (min-width: 1024px) {
            .mobile-nav-link:hover {
                color: #C8A95A;
                background: rgba(200, 169, 90, 0.05);
                padding-left: 32px;
            }
        }

        /* ===== SIDEBAR STYLES ===== */
        .cart-sidebar,
        .wishlist-sidebar {
            position: fixed;
            top: 0;
             font-family: 'Playfair Display', serif;
             left: 0;
            right: auto;
            width: 100%;
            max-width: 360px;
            height: 100vh;
            height: 100dvh;
            background: #ffffff !important;
            backdrop-filter: blur(20px);
            transform: translateX(-100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
            border-left: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
            -webkit-overflow-scrolling: touch;
            color: #1a1a1a !important;
        }
.cart-sidebar .product-card-professional, 
.wishlist-sidebar .product-card-professional {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    padding: 12px !important;
    display: flex !important;
    gap: 12px !important;
}

/* توحيد ألوان النصوص والأحجام داخل السلة والمفضلة */
.cart-sidebar h4, .wishlist-sidebar h4 {
    color: #0f172a !important; /* لون النص أسود دائماً */
    font-size: 14px !important;
}

.cart-sidebar .text-accent, .wishlist-sidebar .text-accent {
    color: #C8A95A !important; /* اللون الذهبي للسعر */
}

/* تثبيت حجم الصور داخل القوائم الجانبية */
.cart-sidebar .w-24, .wishlist-sidebar .w-28 {
    width: 80px !important;
    height: 100px !important;
    object-fit: cover !important;
}
        .cart-sidebar.active,
        .wishlist-sidebar.active {
            transform: translateX(0);
        }

        .cart-sidebar .flex-1.overflow-y-auto,
        .wishlist-sidebar .flex-1.overflow-y-auto {
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        /* ===== OVERLAY STYLES ===== */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 999;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ===== PRODUCT CARD STYLES ===== */
        .product-card-professional {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0;
            margin-bottom: 12px;
            padding: 12px;
        }

        @media (min-width: 1024px) {
            .product-card-professional:hover {
                transform: translateY(-2px);
                box-shadow: 0 15px 35px rgba(15, 23, 42, 0.1), 0 5px 15px rgba(200, 169, 90, 0.1);
                border-color: rgba(200, 169, 90, 0.2);
                cursor: pointer;
            }
        }

      .remove-product-icon {
    position: absolute;
    top: 6px;
    left: 6px;
    /* تمت إزالة الخلفية الثابتة والشفافية من هنا لنعتمد على Tailwind */
    /* background: rgba(15, 23, 42, 0.7);  <-- حذفنا هذا */
    /* color: white; <-- حذفنا هذا */
    border: none;
    border-radius: 50% !important;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    cursor: pointer;
    z-index: 10;
    
    /* هام جداً: حذفنا opacity و visibility من هنا ليعمل الهوفر */
    /* opacity: 1;      <-- حذف */
    /* visibility: visible; <-- حذف */
}
.empty-cart-icon-fix {
    transform: translateZ(0); /* تفعيل تسريع الهاردوير لمنع الرعشة */
    backface-visibility: hidden;
    background-color: transparent !important; /* ضمان عدم وجود خلفية بيضاء */
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
}
.empty-menu-icon {
    object-fit: contain;
    display: block;
}
.cart-sidebar .empty-cart-image {
    width: 96px;
    height: auto;
    max-width: 100%;
    object-fit: contain;
}
        /* ===== SCROLLBAR STYLES ===== */
        .cart-products::-webkit-scrollbar,
        .wishlist-products::-webkit-scrollbar {
            width: 6px;
        }

        .cart-products::-webkit-scrollbar-track,
        .wishlist-products::-webkit-scrollbar-track {
            background: linear-gradient(to bottom, #f1f5f9, #e2e8f0);
            border-radius: 10px;
        }

        .cart-products::-webkit-scrollbar-thumb,
        .wishlist-products::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #C8A95A, #b8965a);
            border-radius: 10px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
        }

        @media (min-width: 1024px) {
            .cart-products::-webkit-scrollbar-thumb:hover,
            .wishlist-products::-webkit-scrollbar-thumb:hover {
                background: linear-gradient(to bottom, #d4b882, #C8A95A);
            }
        }

        /* ===== SEARCH STYLES ===== */
     .full-screen-search {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 110px; /* تم تغيير الارتفاع ليكون صغيراً ومناسباً لحجم الهيدر */
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: translateY(-100%);
    transition: transform 0.3s ease-out;
    visibility: hidden;
    opacity: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* إضافة ظل خفيف لتمييزه */
}


        .full-screen-search.active {
            transform: translateY(0);
            visibility: visible;
            opacity: 1;
        }

        .full-screen-search-content {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        /* توحيد أيقونات الهيدر: أسود فاحم، حجم بارز، وهوفر ذهبي */
.icon-button i {
    font-weight: 5 !important; /* خط عريض جداً */
    font-size: 20px !important; /* حجم بارز */
    -webkit-text-stroke: 0.8px black; /* إضافة سماكة إضافية للأيقونة */
    transition: all 0.3s ease-in-out !important;
}
/* الهوفر للأيقونات */

/* 1. توحيد أيقونات الأكشن (بحث، سلة، مفضلة، مستخدم، قائمة موبايل) */
.icon-button {
    color: black !important; /* اللون الأسود دائماً */
    font-size: 20px !important; /* حجم ثابت */
    transition: all 0.3s ease !important;
    align-items: center !important;
    justify-content: center !important;
    background: transparent !important;
}



/* 2. توحيد أيقونات العملة واللغة (MAD, AR) والسهم */
#currency-dropdown-btn, #language-dropdown-btn {
    color: black !important;
    font-weight: 700 !important;
    font-family: 'Cairo', sans-serif !important;
}


#currency-chevron, #language-chevron {
    color: inherit !important; /* يأخذ لون النص (أسود أو ذهبي عند الهوفر) */
    font-size: 14px !important;
}

/* 3. توحيد أيقونة إغلاق المفضلة والتحكم في الـ Hover */
#close-wishlist, #close-cart, #close-mobile-menu {
    color: #000000 !important;
    transition: all 0.3s ease !important;
    padding: 8px !important;
    border-radius: 50% !important;
}



/* 4. توحيد هوفر روابط قائمة المستخدم */
#auth-dropdown-menu a {
    color: black !important;
    transition: all 0.2s ease !important;
}

/* ابحث عن هذه الأكواد وضعها داخل هذا الشرط */
@media (min-width: 1024px) {
    /* هوفر الأيقونات العامة */
    .icon-button:hover i {
        color: #C8A95A !important;
        -webkit-text-stroke: 0.8px #C8A95A;
        transform: scale(1.1);
    }
    
    .icon-button:hover {
        color: #C8A95A !important;
        transform: translateY(-2px);
    }

    /* هوفر أزرار الإغلاق */
    #close-wishlist:hover, #close-cart:hover, #close-mobile-menu:hover {
        background-color: #f1f5f9 !important;
        color: #ef4444 !important;
        transform: rotate(90deg);
    }

    /* هوفر القائمة المنسدلة للمستخدم */
    #auth-dropdown-menu a:hover {
        background-color: #f8fafc !important;
        color: #C8A95A !important;
        padding-right: 20px !important;
    }
       /* هوفر بطاقة المنتج داخل القائمة الجانبية */
    .wishlist-sidebar .product-card-professional:hover, 
    .cart-sidebar .product-card-professional:hover {
        transform: translateY(-2px) !important;
        border-color: #C8A95A !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important;
    }

    /* هوفر زر الحذف (Trash) داخل القائمة */
    .remove-product-icon:hover {
        background-color: #ef4444 !important;
        color: white !important;
        transform: scale(1.1) !important;
    }
}
        .full-screen-search-input {
            flex-grow: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            background-color: #f1f5f9;
            font-size: 1.25rem;
            color: #0f172a;
            outline: none;
            transition: background-color 0.3s ease;
        }

        .full-screen-search-input:focus {
            background-color: #e2e8f0;
        }

        .full-screen-search-input::placeholder {
            color: #64748b;
        }

        .full-screen-search-icon,
        .full-screen-close-icon {
            background: transparent;
            border: none;
            color: #475569;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 10px;
            transition: color 0.3s ease;
        }

        @media (min-width: 1024px) {
            .full-screen-search-icon:hover,
            .full-screen-close-icon:hover {
                color: #C8A95A;
            }
        }

        /* ===== UTILITY STYLES ===== */
        body.no-scroll {
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .quantity-minus:hover,
            .quantity-plus:hover {
                background: #f1f5f9;
                color: #475569;
            }

            .add-to-cart-btn:hover {
                background: #f1f5f9;
                color: #475569;
            }
        }

        /* ===== RESPONSIVE STYLES ===== */
        @media (max-width: 767px) {
            .logo-text {
                font-size: 20px;
            }

            .nav-menu {
                display: none;
            }

            .mobile-menu #close-mobile-menu .icon-button {
                padding: 10px;
            }

            .mobile-menu .logo-text {
                font-size: 16px;
            }

            .mobile-menu .logo-subtitle {
                font-size: 9px;
            }



    /* 4. Make links Black */
            .nav-link {
                color: #000000 !important;
                font-size: 13px;
                padding: 10px 12px;
                margin: 0 2px;
                white-space: nowrap;
            }

    /* تعديل النصوص للروابط المحددة */
    .nav-link-parfums-mens::before { content: "عطور"; }
    .nav-link-parfums-womens::before { content: "إكسسوارات"; }
    .nav-link-accessories-mens::before { content: "ساعات"; }
    .nav-link-accessories-womens::before { content: ""; }

    /* إخفاء النص الأصلي للروابط وتعديل العرض لجعلها تظهر في سطر واحد */
    .nav-menu .nav-link:not(.nav-link-home):not(.nav-link-packs) span {
        display: none;
    }

    .nav-menu .nav-link:not(.nav-link-home):not(.nav-link-packs) {
        position: relative;
    }
    .nav-menu .nav-link:not(.nav-link-home):not(.nav-link-packs)::before {
        display: block;
    }

    /* تعديل خاص لـ ACCESSORIES لجعله يظهر مرة واحدة */
    .nav-link-accessories-womens {
        display: none; /* إخفاء رابط ACCESSORIES WOMENS لمنع التكرار */
    }

  /* 2. Fix scrolling: Enable touch swipe & Add padding so last link is seen */
            .nav-menu .flex {
                display: flex !important;
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                
                /* CRITICAL: These two lines fix the finger scrolling on mobile */
                -webkit-overflow-scrolling: touch !important; 
                touch-action: pan-x !important; 

                justify-content: flex-start !important; /* Starts items from right (RTL) */
                padding: 10px 20px !important; /* Padding ensures 'Watches' isn't cut off */
                width: 100% !important;
                scrollbar-width: none; /* Hide scrollbar Firefox */
            }
    /* إخفاء شريط التمرير (Scrollbar) للحفاظ على المظهر الجميل في كروم وسفاري */
    .nav-menu .flex::-webkit-scrollbar {
        display: none;
    }

        }
/* يمكنك إضافة Media Query منفصلة للتحكم الدقيق في التسميات إذا كان هناك تداخل */

        @media (max-width: 767px) {

            /* Disable hover effects on mobile */
            .product-card-professional:hover {
                transform: none;
                box-shadow: none;
                border-color: rgba(226, 232, 240, 0.6);
            }

            .icon-button:hover {
                color: #475569;
            }

            .nav-link:hover {
                color: #475569;
                background: transparent;
            }

            .nav-link:hover::after {
                width: 0;
            }

            .btn-professional:hover {
                background: linear-gradient(135deg, #C8A95A, #d4b882);
                transform: none;
                box-shadow: 0 4px 15px rgba(200, 169, 90, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }

            .btn-whatsappp:hover {
                background: linear-gradient(135deg, #25D366, #1DA851);
                transform: none;
                box-shadow: 0 4px 15px rgba(37, 211, 102, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }

            .full-screen-search-icon:hover,
            .full-screen-close-icon:hover {
                color: #475569;
            }

            .quantity-minus:hover,
            .quantity-plus:hover {
                background: #f1f5f9;
                color: #475569;
            }

            .add-to-cart-btn:hover {
                background: #f1f5f9;
                color: #475569;
            }

            #close-cart:hover,
            #close-wishlist:hover {
                color: #64748b;
                transform: none;
                background: transparent;
            }

            .mobile-nav-link:hover {
                background: transparent;
                color: #475569;
            }

            .wishlist-sidebar,
            .cart-sidebar {
                width: 100%;
                max-width: 100%;
                left: 0;
                height: 100vh;
                height: 100dvh;
                min-height: 100vh;
                min-height: 100dvh;
            }

            .wishlist-sidebar .flex-1.overflow-y-auto,
            .cart-sidebar .flex-1.overflow-y-auto {
                padding-bottom: 120px;
            }

            .wishlist-sidebar .p-8.border-t,
            .cart-sidebar .p-8.border-t {
                padding: 16px 20px;
                position: sticky;
                bottom: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                border-top: 1px solid #e2e8f0;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            }

            .cart-sidebar .btn-whatsappp,
            .wishlist-sidebar .btn-professional {
                padding: 16px 20px;
                font-size: 0.9rem;
                min-height: 48px;
            }

            .full-screen-search {
                height: 70px;
            }

            .full-screen-search-input {
                padding: 10px 15px;
                font-size: 1rem;
            }

            .full-screen-search-icon,
            .full-screen-close-icon {
                font-size: 1.2rem;
                padding: 5px;
            }
        }

        @media (max-width: 480px) {

            .wishlist-sidebar .flex-1.overflow-y-auto,
            .cart-sidebar .flex-1.overflow-y-auto {
                padding-bottom: 140px;
            }

            .cart-sidebar .p-8.border-t,
            .wishlist-sidebar .p-8.border-t {
                padding: 12px 16px;
            }
        }

        /* ===== COMPONENT SIZE ADJUSTMENTS ===== */
        .cart-sidebar .flex-shrink-0,
        .wishlist-sidebar .flex-shrink-0 {
            padding: 20px;
        }

        .cart-sidebar .flex-shrink-0 .logo-text,
        .wishlist-sidebar .flex-shrink-0 .logo-text {
            font-size: 16px;
        }

        .cart-sidebar .flex-shrink-0 .logo-subtitle,
        .wishlist-sidebar .flex-shrink-0 .logo-subtitle {
            font-size: 8px;
        }

        .product-card-professional .w-24 {
            width: 80px;
        }

        .product-card-professional .h-32 {
            height: 100px;
        }

        .product-card-professional .font-semibold.text-sm {
            font-size: 0.8rem;
        }

        .product-card-professional .font-bold.text-sm {
            font-size: 0.8rem;
        }

        .product-card-professional .text-xs {
            font-size: 0.65rem;
        }

        .product-card-professional .w-7,
        .product-card-professional .h-7 {
            width: 24px;
            height: 24px;
        }

        .product-card-professional .quantity-display {
            font-size: 0.8rem;
        }

        .cart-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
  #auth-dropdown-menu {
    border-radius: 15px !important;
    overflow: hidden !important; /* هذا السطر يمنع العناصر الداخلية من تغطية الحواف الدائرية */
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

/* جعل أزرار تسجيل الدخول دائرية أيضاً عند الهوفر */
#auth-dropdown-menu a {
    transition: background 0.3s;
}
/* تثبيت ستايل أزرار العملة واللغة لعدم التغير بين الصفحات */
#currency-dropdown-btn, 
#language-dropdown-btn {
    font-family: 'Cairo', sans-serif !important; /* توحيد الخط */
    font-size: 14px !important;                /* حجم الخط (text-sm) */
    font-weight: bold !important;               /* سمك الخط (font-semibold) */
    color: black !important;                 /* لون النص الأساسي */
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;                       /* المسافة بين العلم والنص */
    border: none !important;
    padding: 0 12px !important;
}

/* تثبيت اللون عند الهوفر (الذهبي) */
@media (min-width: 1024px) {
    #currency-dropdown-btn:hover, 
    #language-dropdown-btn:hover {
        color: #C8A95A !important;
    }
}

/* التأكد من ثبات حجم الأيقونة (السهم) */
#currency-chevron, 
#language-chevron {
    font-size: 12px !important;
    color: inherit !important; /* يأخذ لون النص (رمادي أو ذهبي عند الهوفر) */
}

/* تثبيت حجم علم اللغة لكي لا يتغير حجمه */
#language-flag-icon {
    width: 20px !important;
    height: 14px !important;
    object-fit: cover !important;
    border-radius: 2px !important;
}
.header-account-btn {
    border-color: var(--accent-color, #C8A95A) !important;
    color: var(--accent-color, #C8A95A) !important;
    background-color: transparent !important;

    transition: 
        background-color 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease !important;
}

/* Hover & Focus */
.header-account-btn:focus {
    background-color: var(--accent-color, #C8A95A) !important;
    color: #fff !important;
    border-color: var(--accent-color, #C8A95A) !important;
}

@media (min-width: 1024px) {
    .header-account-btn:hover {
        background-color: var(--accent-color, #C8A95A) !important;
        color: #fff !important;
        border-color: var(--accent-color, #C8A95A) !important;
    }
}
/* الستايل الافتراضي للروابط (يمنع ظهور لونين في التابلت) */
.nav-link.active {
    color: black !important;
    background: transparent !important;
}
.nav-link.active::after {
    display: none !important;
}

/* الستايل النشط الحقيقي يظهر فقط في الشاشات الكبيرة جداً */
@media (min-width: 1024px) {
    .nav-link.active {
        color: #C8A95A !important; /* اللون الذهبي */
        background: rgba(200, 169, 90, 0.1) !important;
    }
    .nav-link.active::after {
        display: block !important;
        width: 60% !important;
    }
}
    </style>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- ===== MAIN HEADER ===== -->
    <header class="header-modern fixed top-0 left-0 w-full z-50">
        <!-- Top Header Bar -->
        <!-- تم تغيير px-4 إلى px-2 للموبايل (لتوسيع المساحة) و px-6 للكمبيوتر (للترتيب) -->
<div class="container mx-auto px-2 md:px-6 py-3">
                <div class="flex items-center justify-between relative">
                <!-- Left Section: Mobile Menu & Controls -->
              <!-- في الحجم الكبير md:gap-2 تعني مسافة صغيرة جداً ومحكمة -->
<div class="flex items-center gap-1.5 md:gap-2 z-10">
                    <button class="icon-button w-9 h-9 flex items-center justify-center md:hidden" id="mobile-menu-btn">
       <i class="ph ph-list text-2xl"></i>
                    </button>
                    <button class="icon-button w-9 h-9 flex items-center justify-center md:hidden"
                        id="mobile-search-btn" onclick="openFullScreenSearch()">
                        <i class="ph ph-magnifying-glass text-xl"></i>
                    </button>

                    <!-- Currency Dropdown -->
                    <div class="relative group hidden md:block" style="direction: ltr;">
                        <!-- لاحظ أننا أضفنا gap-2 وحذفنا ml-2 من الأيقونة -->
<button id="currency-dropdown-btn" class="icon-button w-auto px-3 h-9 flex items-center justify-center gap-2 text-sm font-semibold text-text-secondary lg:hover:text-accent transition-colors duration-200">
    <span id="selected-currency">MAD</span>
    <i id="currency-chevron" class="ph ph-caret-down text-xs transition-transform duration-200"></i>
</button>
                        <div id="currency-dropdown-menu"
                            class="absolute left-0 mt-2 w-32  rounded-lg  py-1 z-50 opacity-0 scale-95 invisible transition-all duration-200 ease-out origin-top-left ">
                    
                          
                          
                        </div>
                    </div>

                    <!-- Language Dropdown -->
                    <div class="relative group hidden md:block" style="direction: ltr;">
                       <!-- لاحظ أننا أضفنا gap-2 وحذفنا mr-2 و ml-2 من الصور والأيقونات -->
<button id="language-dropdown-btn" class="icon-button w-auto px-3 h-9 flex items-center justify-center gap-2 text-sm font-semibold text-text-secondary lg:hover:text-accent transition-colors duration-200">
    <img id="language-flag-icon" src="https://flagcdn.com/w40/ma.png" alt="Flag" class="w-5 h-3 object-cover shadow-sm">
    <span id="selected-language">AR</span>
    <i id="language-chevron" class="ph ph-caret-down text-xs transition-transform duration-200"></i>
</button>
                        <div id="language-dropdown-menu"
                            class="absolute left-0 mt-2 w-32  rounded-lg  py-1 z-50 opacity-0 scale-95 invisible transition-all duration-200 ease-out origin-top-left ">
                   
                        </div>
                    </div>
                </div>

                <!-- Center Section: Logo -->
                <div class="absolute left-1/2 -translate-x-1/2 flex items-center space-x-3 z-0" style="direction: ltr;">
                                  <!-- logo 1-->

                <img src="public/images/logo.png" alt="STORE Logo" class="h-12 w-auto object-contain">
                    <div class="hidden sm:block">
                        <a href="index.php">
                        <div class="logo-text">Abdelwahab</div>
                        <div class="logo-subtitle">ACCESSORIES & PARFUMS</div>
                    </div></a>
                </div>

                <!-- Right Section: Action Buttons -->
<!-- تم تعديل المسافات: gap-1.5 للموبايل (متلاصقة وأنيقة) و gap-5 للكمبيوتر (مريحة للعين) -->
<div class="flex items-center gap-1.5 md:gap-1 z-10">                  
      <button aria-label="سلة التسوق" class="icon-button w-9 h-9 flex items-center justify-center" id="cart-btn">
                        <i class="ph ph-bag-simple text-xl"></i>
                        <!-- Updated cart badge from 4 to 1 -->
<span class="cart-badge" style="<?php echo ($cartCount > 0) ? 'display:flex' : 'display:none'; ?>">
    <?php echo $cartCount; ?>
</span>                 </button>
                    <button aria-label="بحث" class="icon-button w-9 h-9 flex items-center justify-center hidden md:flex"
                        id="desktop-search-btn">
                        <i class="ph ph-magnifying-glass text-xl"></i>
                    </button>
                    
                    <button aria-label="المفضلة" class="icon-button w-9 h-9 flex items-center justify-center" id="wishlist-btn">
                        <i class="ph ph-heart text-xl"></i>
                        <!-- Updated wishlist badge from 3 to 1 -->
<span class="wishlist-badge" style="<?php echo ($wishlistCount > 0) ? 'display:flex' : 'display:none'; ?>">
    <?php echo $wishlistCount; ?>
</span>                    </button>

                    <!-- User Authentication Dropdown -->
                    <div class="relative group">
                        <button class="icon-button w-9 h-9 flex items-center justify-center" id="auth-dropdown-btn">
                            <i class="ph ph-user text-xl"></i>
                        </button>
                        <div id="auth-dropdown-menu"
class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-0 z-50 opacity-0 scale-95 invisible transition-all duration-200 ease-out origin-top-right border border-border overflow-hidden">                            <!-- Removed My Orders and Wishlist buttons -->
                                    <?php if ($isLoggedIn): ?>
                            <a href="#"
                                class="block px-4 py-2 text-sm text-text-secondary lg:hover:bg-surface-hover lg:hover:text-accent flex items-center">
<i class="ph ph-user mr-3"style="margin-left: 5px;"></i> ملفي الشخصي
                            </a>
                            <a href="#"
                                class="block px-4 py-2 text-sm text-text-secondary lg:hover:bg-surface-hover lg:hover:text-accent flex items-center">
                                <i class="ph ph-question mr-3 flex-shrink-0"style="margin-left: 5px;"></i>
                                <span class="whitespace-nowrap">المساعدة والدعم </span>
                            </a>
                            <div class="border-t border-border my-1"></div>
                           <a href="logout.php" class="block px-4 py-2 text-sm text-text-secondary lg:hover:bg-surface-hover lg:hover:text-accent flex items-center">
    <i class="ph ph-sign-out mr-3" style="margin-left: 5px;"></i> 
    تسجيل الخروج
</a>
                            <?php else: ?>
                               <a href="register.php"
                class="block px-4 py-2 text-sm text-text-secondary lg:hover:bg-surface-hover lg:hover:text-accent flex items-center">
                <i class="ph ph-lock mr-3" style="margin-left: 5px;"></i> تسجيل الدخول
              </a>
              <a href="register.php"
                class="block px-4 py-2 text-sm text-text-secondary lg:hover:bg-surface-hover lg:hover:text-accent flex items-center">
                <i class="ph ph-user mr-3" style="margin-left: 5px;"></i> إنشاء حساب
              </a>
                <?php endif; ?>
                        </div>
                    </div>

                   
                </div>
            </div>
        </div>
<!-- نموذج خفي لتسجيل الخروج -->
<form id="logout-form" action="logout.php" method="POST" style="display: none;">
    <input type="hidden" name="logout" value="1">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
</form>
        <!-- Navigation Menu -->
      <!-- Navigation Menu -->
        <!-- Navigation Menu -->
     <!-- Navigation Menu -->
<nav class="nav-menu hidden md:block">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-center w-full">
            
            <a href="index.php" class="nav-link <?php echo ($activeCategory == 'index') ? 'active' : ''; ?>">
                <span>الصفحة الرئيسية</span>
            </a>

            <a href="filter.php" class="nav-link <?php echo ($activeCategory == 'filter') ? 'active' : ''; ?>">
                <span>صفحة المنتجات</span>
            </a>

            <a href="filter.php?categurie=باقات وعروض" class="nav-link <?php echo ($activeCategory == 'pack') ? 'active' : ''; ?>">
                <span>الباقات الجديدة</span>
            </a>

            <!-- العطور: ستختفي في التابلت (md) وتظهر في الكمبيوتر (lg) -->
           <!-- رابط العطور -->
<a href="filter.php?categurie=عطور" class="nav-link md:!hidden lg:!flex <?php echo ($activeCategory == 'parfums') ? 'active' : ''; ?>">
    <span>العطور</span>
</a>



            <a href="filter.php?categurie=الإكسسوارات" class="nav-link <?php echo ($activeCategory == 'accessories') ? 'active' : ''; ?>">
                <span>الإكسسوارات</span>
            </a>

          <!-- رابط الساعات -->
<a href="filter.php?categurie=ساعات" class="nav-link md:!hidden lg:!flex <?php echo ($activeCategory == 'watches') ? 'active' : ''; ?>">
    <span>الساعات</span>
</a>

        </div>
    </div>
</nav>
    </header>

    <!-- ===== FULL SCREEN SEARCH ===== -->
    <div class="full-screen-search" id="full-screen-search">
        <div class="full-screen-search-content">
<input type="text" class="full-screen-search-input text-right" placeholder="ابحث في المتجر...">
            <button class="full-screen-search-icon">
                <i class="ph ph-magnifying-glass"></i>
            </button>
            <button class="full-screen-close-icon" aria-label="بحث" id="full-screen-close-search">
                <i class="ph ph-x"></i>
            </button>
        </div>
    </div>

    <!-- ===== MOBILE MENU ===== -->
    <div class="mobile-menu" id="mobile-menu" style="direction: ltr;">
        <!-- Mobile Menu Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                            <!-- logo 2 -->

                    <img src="public/images/logo.png" alt="DJELLABTI Logo" class="h-10 w-auto object-contain">
                    <div>
                        <a href="index.php">
                        <div class="logo-text text-lg">Abdolwahab</div>
                        <div class="logo-subtitle" style="font-size: bold;color:#C8A95A;">ACCESSORIES & Watches</div>
                    </div></a>
                </div>
                <button id="close-mobile-menu" aria-label="فتح القائمة" class="icon-button w-9 h-9 flex items-center justify-center">
                    <i class="ph ph-x text-base"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Settings -->
        <div class="p-6 border-b border-gray-200" style="direction: rtl;">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">العملة</label>
                    <select id="mobile-currency-select"
                        class="w-full p-3 border border-gray-300 rounded-lg bg-white text-gray-700">
                        <option value="د.ر" selected>د.ر</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">اللغة</label>
                    <select id="mobile-language-select"
                        class="w-full p-3 border border-gray-300 rounded-lg bg-white text-gray-700">
                        <option value="AR" data-flag="https://flagcdn.com/w40/ma.png" selected>العربية</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <nav class="py-4" style="direction: rtl;">
            <a href="index.php" class="mobile-nav-link">الصفحة الرئيسية</a>
             <a href="filter.php?" class="mobile-nav-link">صفحة المنتجات </a>
            <a href="filter.php?categurie=باقات وعروض" class="mobile-nav-link">الباقاة الجديدة</a>
            <a href="filter.php?categurie=عطور" class="mobile-nav-link">العطور</a>
            <a href="filter.php?categurie=الإكسسوارات" class="mobile-nav-link">الإكسسوارات</a>
            <a href="filter.php?categurie=ساعات" class="mobile-nav-link">الساعات</a>
        </nav>
    </div>
<!-- ===== WISHLIST SIDEBAR ===== -->
<div class="wishlist-sidebar flex flex-col h-full" id="wishlist-sidebar" style="direction: ltr;">

  <!-- Wishlist Header -->
  <div class="flex items-center justify-between p-8 border-b border-border  from-surface-elevated to-surface flex-shrink-0" style="background: #ffffff !important;">
      <div class="flex items-center">
        <!-- logo 3 -->

          <img src="public/images/logo.png" alt="DJELLABTI Logo" class="h-12 w-auto object-contain mr-4">
          <div>
              <h2 class="font-playfair font-bold text-lg gradient-text">NEW BRAND</h2>
              <p class="text-xs text-text-muted font-medium tracking-wide">MY WISHLIST</p>
          </div>
      </div>
      <button id="close-wishlist"
          class="text-text-secondary lg:hover:text-accent focus:outline-none transition-all duration-200 lg:hover:scale-110 p-1.5 rounded-lg lg:hover:bg-surface-hover">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
      </button>
  </div>

  <!-- Wishlist Products -->
 <!-- ===== WISHLIST SIDEBAR ===== -->
<div class="flex-1 overflow-y-auto wishlist-products p-6" id="wishlist-items-container" style="direction: rtl;">
    <?php if (!$isLoggedIn): ?>
        <!-- حالة المستخدم غير مسجل الدخول -->
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
            <div class="mb-6">
                <img src="public/images/empty%20wishlist.png" alt="Empty" class="w-24 h-24 mx-auto opacity-90 mb-4 empty-menu-icon">        
                <h3 class="font-playfair font-bold text-xl text-text-primary mb-2">قائمة المفضلة فارغة</h3>
                <p class="text-text-secondary text-sm leading-relaxed mb-6 font-sans">أضف منتجاتك المفضلة الآن لتخزينها لوقت لاحق</p>
            </div>
            <div class="space-y-3 w-full max-w-xs font-sans">
                <a href="register.php" class="w-full btn-professional text-white py-3 font-bold text-sm uppercase tracking-wide flex items-center justify-center gap-2 group">
                    <span>تسجيل الدخول</span>
                    <i class="ph ph-sign-in text-lg lg:group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- حالة المستخدم مسجل دخول -->
        <div id="wishlist-content-wrapper">
            <?php if ($wishlistCount > 0): ?>
                <div class="text-xs font-bold mb-4 text-right" style="font-size: 14px;">لديك <span id="wishlist-badge-count"><?php echo $wishlistCount; ?></span> عناصر في قائمة الأمنيات.</div>
                <div class="space-y-4" id="wishlist-items-list">
                    <?php foreach ($wishlistProductsData as $item): ?>
                        <!-- كرت المنتج (نفس الستايل الخاص بك) -->
                        <div class="wishlist-item-row product-card-professional group p-4 flex flex-row items-start gap-5 relative transition-all duration-300 bg-white" data-id="<?php echo $item['id']; ?>">
                            <a href="product.php?id=<?php echo $item['id']; ?>" class="flex flex-row items-start gap-5 w-full">
                        <div class="w-28 h-36 flex-shrink-0 overflow-hidden relative rounded-sm">
                                <div class="w-full h-full bg-cover bg-center transition-transform duration-700"
                                     style="background-image: url('<?php echo $item['images'][0]['src'] ?? ''; ?>');">
                                </div>
                                <!-- زر الحذف AJAX -->
                                <button onclick="toggleWishlist(this, event)" data-product-id="<?php echo $item['id']; ?>" class="remove-product-icon absolute top-2 left-2 w-8 h-8 bg-white/90 backdrop-blur-sm text-red-500 rounded-full flex items-center justify-center shadow-sm">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>
                            <div class="flex flex-col flex-grow min-w-0 h-full justify-between py-1">
                                <div>
                                    <h4 class="font-serif font-bold text-base text-gray-900 leading-tight mb-2"><?php echo $item['name']; ?></h4>
                                    <p class="text-xs text-gray-500 font-medium mb-2">التصنيف: <?php echo $item['categories'][0]['name'] ?? ''; ?></p>
                                </div>
                                <div class="flex items-end gap-2 mt-auto whitespace-nowrap">
                                    <span class="font-bold text-base text-gray-900"><?php echo $item['price']; ?> د.م</span>
                                </div>
                            </div>
                             </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- حالة القائمة فارغة للمسجلين -->
                <div class="text-center py-20">
                    <img src="public/images/empty%20wishlist.png" class="w-20 mx-auto opacity-50 mb-4 empty-menu-icon">
                    <h3 class="text-gray-500 font-bold">مفضلتك فارغة حالياً</h3>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

  <!-- Wishlist Footer (يظهر فقط للمستخدم المسجل) -->
  <?php if ($isLoggedIn): ?>
  <div class="p-8 border-t border-border bg-gradient-to-r from-surface-elevated to-surface flex-shrink-0">
      <div class="space-y-3">
         <button id="add-all-wishlist-to-cart" onclick="addAllWishlistToCart()" class="w-full btn-professional text-white py-4 font-bold text-base uppercase tracking-wider flex items-center justify-center gap-3 shadow-lg lg:hover:shadow-accent/40 transition-all duration-300">
    <span>إضافة الكل للسلة</span>
    <i class="ph ph-bag-plus text-xl"></i>
</button>
      </div>
  </div>
  <?php endif; ?>

</div>

   

    <!-- ===== CART SIDEBAR ===== -->
    <div class="cart-sidebar" id="cart-sidebar">
        <!-- Cart Header -->
        <div
            class="flex items-center justify-between p-8 border-b border-border  from-surface-elevated to-surface flex-shrink-0" style="direction: ltr;background: #ffffff !important;">
            <div class="flex items-center">
                <!-- logo 4 -->
                <img src="public/images/logo.png" alt="DJELLABTI Logo" class="h-12 w-auto object-contain mr-4">
                <div>
                    <h2 class="font-playfair font-bold text-lg gradient-text">NEW BRAND</h2>
                    <p class="text-xs text-text-muted font-medium tracking-wide">SHOPPING CART</p>
                </div>
            </div>
            <button id="close-cart"
                class="text-text-secondary lg:hover:text-accent focus:outline-none transition-all duration-200 lg:hover:scale-110 p-1.5 rounded-lg lg:hover:bg-surface-hover">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Cart Products -->
        <div id="cart-products-container" class="flex-1 overflow-y-auto cart-products p-6">
           

            
        </div>
 <?php if ($isLoggedIn): ?>
        <!-- Cart Footer -->
       <div 
    style="
        direction: rtl;
        font-family: 'Tajawal', sans-serif;
        background: linear-gradient(to left, #FFFFFF, #F9FAFB);
        border-top: 1px solid #E5E7EB;
        padding: 2rem;
        flex-shrink: 0;
    "
>

    <!-- الشحن -->
    <div 
        style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.94rem;
            margin-bottom: 1rem;
        "
    >
<span style="color:#6B7280; font-weight:500;">الشحن:</span>
        <span  id="cart-shipping" style="color:#059669; font-weight:600;">مجاني</span>
    </div>

    <!-- الضريبة -->
    <div 
        style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.94rem;
            margin-bottom: 1rem;
        "
    >
        <span style="color:#6B7280; font-weight:500;">الضريبة:</span>
        <span id="cart-tax" style="color:#111827; font-weight:600;">0.00 د.م</span>
    </div>

    <!-- الإجمالي -->
    <div 
        style="
            border-top: 1px solid #E5E7EB;
            padding-top: 1rem;
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        "
    >
        <span style="font-size:1.2rem; font-weight:700; color:#111827;">الإجمالي:</span>
<span id="cart-total" style="font-size:1.1rem; font-weight:700; color: #C8A95A;">0.00 د.م</span>
    </div>

</div>
          <button
    class="w-full btn-whatsappp text-white py-4 font-bold flex items-center justify-center space-x-2"
    style="font-family: 'Tajawal', sans-serif; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em;"
onclick="buyCartViaWhatsapp()">
    <span style="font-weight:700;">اطلب عبر واتساب</span> 
</button>

        </div>
         <?php endif ?>
    </div>

    <!-- ===== OVERLAY ===== -->
    <div class="overlay" id="overlay"></div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        // ===== DOM ELEMENT REFERENCES =====
        const elements = {
            // Mobile Menu
            mobileMenuBtn: document.getElementById('mobile-menu-btn'),
            mobileMenu: document.getElementById('mobile-menu'),
            closeMobileMenu: document.getElementById('close-mobile-menu'),

            // Cart
            cartBtn: document.getElementById('cart-btn'),
            cartSidebar: document.getElementById('cart-sidebar'),
            closeCart: document.getElementById('close-cart'),

            // Wishlist
            wishlistBtn: document.getElementById('wishlist-btn'),
            wishlistSidebar: document.getElementById('wishlist-sidebar'),
            closeWishlist: document.getElementById('close-wishlist'),

            // Search
            desktopSearchBtn: document.getElementById('desktop-search-btn'),
            fullScreenSearch: document.getElementById('full-screen-search'),
            fullScreenCloseSearch: document.getElementById('full-screen-close-search'),
            fullScreenSearchInput: document.querySelector('.full-screen-search-input'),

            // Dropdowns
            currencyDropdownBtn: document.getElementById('currency-dropdown-btn'),
            currencyDropdownMenu: document.getElementById('currency-dropdown-menu'),
            selectedCurrencySpan: document.getElementById('selected-currency'),
            currencyOptions: document.querySelectorAll('.currency-option'),
            currencyChevron: document.getElementById('currency-chevron'),

            languageDropdownBtn: document.getElementById('language-dropdown-btn'),
            languageDropdownMenu: document.getElementById('language-dropdown-menu'),
            selectedLanguageSpan: document.getElementById('selected-language'),
            languageOptions: document.querySelectorAll('.language-option'),
            languageChevron: document.getElementById('language-chevron'),
            languageFlagIcon: document.getElementById('language-flag-icon'),

            authDropdownBtn: document.getElementById('auth-dropdown-btn'),
            authDropdownMenu: document.getElementById('auth-dropdown-menu'),

            // Utility
            overlay: document.getElementById('overlay'),
            body: document.body,
            header: document.querySelector('header')
        };

        // ===== UTILITY FUNCTIONS =====
        function closeAllMenus() {
            // Close all menus and sidebars
            if (elements.mobileMenu) elements.mobileMenu.classList.remove('active');
            if (elements.cartSidebar) elements.cartSidebar.classList.remove('active');
            if (elements.wishlistSidebar) elements.wishlistSidebar.classList.remove('active');
            if (elements.fullScreenSearch) elements.fullScreenSearch.classList.remove('active');
            if (elements.overlay) elements.overlay.classList.remove('active');

            // Restore body scroll
            if (elements.body) {
                elements.body.classList.remove('no-scroll');
                elements.body.style.overflow = '';
            }

            // Close dropdowns and reset chevrons
            if (elements.currencyDropdownMenu) {
                closeDropdown(elements.currencyDropdownMenu, elements.currencyChevron);
            }
            if (elements.languageDropdownMenu) {
                closeDropdown(elements.languageDropdownMenu, elements.languageChevron);
            }
            if (elements.authDropdownMenu) {
                closeDropdown(elements.authDropdownMenu);
            }
        }

        function closeDropdown(menu, chevron = null) {
            if (!menu) return;
            menu.classList.add('opacity-0', 'scale-95', 'invisible');
            menu.classList.remove('opacity-100', 'scale-100', 'visible');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }

        function openDropdown(menu, chevron = null) {
            closeAllMenus();
            if (!menu) return;
            menu.classList.remove('opacity-0', 'scale-95', 'invisible');
            menu.classList.add('opacity-100', 'scale-100', 'visible');
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        }

        function animateItems(container, selector) {
            if (!container) return;
            const items = container.querySelectorAll(selector);
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        // ===== MENU FUNCTIONS =====
        function openCart() {
            closeAllMenus();
            if (!elements.cartSidebar || !elements.overlay || !elements.body) return;
            elements.cartSidebar.classList.add('active');
            elements.overlay.classList.add('active');
            elements.body.classList.add('no-scroll');
            animateItems(elements.cartSidebar, '.product-card-professional');
        }

        function openWishlist() {
            closeAllMenus();
            if (!elements.wishlistSidebar || !elements.overlay || !elements.body) return;
            elements.wishlistSidebar.classList.add('active');
            elements.overlay.classList.add('active');
            elements.body.classList.add('no-scroll');
            animateItems(elements.wishlistSidebar, '.product-card-professional');
        }

        function openMobileMenu() {
            closeAllMenus();
            if (!elements.mobileMenu || !elements.overlay || !elements.body) return;
            elements.mobileMenu.classList.add('active');
            elements.overlay.classList.add('active');
            elements.body.classList.add('no-scroll');
        }

        function openFullScreenSearch() {
            closeAllMenus();
            if (!elements.fullScreenSearch || !elements.body) return;
            elements.fullScreenSearch.classList.add('active');
            elements.body.classList.add('no-scroll');
            if (elements.fullScreenSearchInput) {
                elements.fullScreenSearchInput.focus();
            }
        }

        function closeFullScreenSearch() {
            if (!elements.fullScreenSearch || !elements.body) return;
            elements.fullScreenSearch.classList.remove('active');
            elements.body.classList.remove('no-scroll');
            elements.body.style.overflow = '';
        }

        // ===== EVENT LISTENERS =====

        // Cart Events
        if (elements.cartBtn) {
            elements.cartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openCart();
            });
        }

        if (elements.closeCart) {
            elements.closeCart.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeAllMenus();
            });
        }

        // Wishlist Events
        if (elements.wishlistBtn) {
            elements.wishlistBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openWishlist();
            });
        }

        if (elements.closeWishlist) {
            elements.closeWishlist.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeAllMenus();
            });
        }

        // Mobile Menu Events
        if (elements.mobileMenuBtn) {
            elements.mobileMenuBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openMobileMenu();
            });
        }

        if (elements.closeMobileMenu) {
            elements.closeMobileMenu.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeAllMenus();
            });
        }

        // Search Events
        if (elements.desktopSearchBtn) {
            elements.desktopSearchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openFullScreenSearch();
            });
        }

        if (elements.fullScreenCloseSearch) {
            elements.fullScreenCloseSearch.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeFullScreenSearch();
            });
        }

        // Dropdown Events
        if (elements.currencyDropdownBtn && elements.currencyDropdownMenu) {
            elements.currencyDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (elements.currencyDropdownMenu.classList.contains('visible')) {
                    closeAllMenus();
                } else {
                    openDropdown(elements.currencyDropdownMenu, elements.currencyChevron);
                }
            });
        }

        if (elements.languageDropdownBtn && elements.languageDropdownMenu) {
            elements.languageDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (elements.languageDropdownMenu.classList.contains('visible')) {
                    closeAllMenus();
                } else {
                    openDropdown(elements.languageDropdownMenu, elements.languageChevron);
                }
            });
        }

        if (elements.authDropdownBtn && elements.authDropdownMenu) {
            elements.authDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (elements.authDropdownMenu.classList.contains('visible')) {
                    closeAllMenus();
                } else {
                    openDropdown(elements.authDropdownMenu);
                }
            });
        }

        // Overlay Events
        if (elements.overlay) {
            elements.overlay.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                closeAllMenus();
            });
        }

        // Prevent sidebar content clicks from closing
        [elements.cartSidebar, elements.wishlistSidebar, elements.mobileMenu].filter(Boolean).forEach(element => {
            element.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        // Full-screen search events
        if (elements.fullScreenSearch) {
            const searchContent = elements.fullScreenSearch.querySelector('.full-screen-search-content');
            if (searchContent) {
                searchContent.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            elements.fullScreenSearch.addEventListener('click', (e) => {
                if (e.target === elements.fullScreenSearch) {
                    closeFullScreenSearch();
                }
            });
        }

        // ===== DROPDOWN FUNCTIONALITY =====

        // Currency Selection
        elements.currencyOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                elements.selectedCurrencySpan.textContent = option.dataset.currency;

                // Update checkmarks
                elements.currencyOptions.forEach(opt => {
                    const check = opt.querySelector('.selected-check');
                    if (check) check.style.display = 'none';
                });

                const selectedCheck = option.querySelector('.selected-check');
                if (selectedCheck) selectedCheck.style.display = 'block';

                closeAllMenus();
            });
        });

        // Language Selection
        function changeLanguage(lang, flagUrl, name) {
            elements.selectedLanguageSpan.textContent = lang.toUpperCase();
            if (elements.languageFlagIcon) {
                elements.languageFlagIcon.src = flagUrl;
                elements.languageFlagIcon.alt = `${name} Flag`;
            }

            // Remove existing checkmarks
            elements.languageOptions.forEach(opt => {
                const checkIcon = opt.querySelector('.selected-check');
                if (checkIcon) {
                    checkIcon.remove();
                }
            });

            // Add checkmark to selected option
            const selectedOption = document.querySelector(`.language-option[data-lang="${lang}"]`);
            if (selectedOption) {
                selectedOption.innerHTML += '<i class="ph ph-check ml-auto text-accent selected-check"></i>';
            }

            closeAllMenus();
            document.documentElement.lang = lang.toLowerCase();
        }

        elements.languageOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                const lang = option.dataset.lang;
                const flagUrl = option.dataset.flag;
                const name = option.querySelector('span')?.textContent || lang;
                changeLanguage(lang, flagUrl, name);
            });
        });

    

        // Click Outside Dropdowns
        document.addEventListener('click', (e) => {
            // Close currency dropdown if click is outside
            if (elements.currencyDropdownBtn && elements.currencyDropdownMenu) {
                if (!elements.currencyDropdownBtn.contains(e.target) && !elements.currencyDropdownMenu.contains(e.target)) {
                    closeDropdown(elements.currencyDropdownMenu, elements.currencyChevron);
                }
            }

            // Close language dropdown if click is outside
            if (elements.languageDropdownBtn && elements.languageDropdownMenu) {
                if (!elements.languageDropdownBtn.contains(e.target) && !elements.languageDropdownMenu.contains(e.target)) {
                    closeDropdown(elements.languageDropdownMenu, elements.languageChevron);
                }
            }

            // Close auth dropdown if click is outside
            if (elements.authDropdownBtn && elements.authDropdownMenu) {
                if (!elements.authDropdownBtn.contains(e.target) && !elements.authDropdownMenu.contains(e.target)) {
                    closeDropdown(elements.authDropdownMenu);
                }
            }
        });

        // ===== INITIALIZATION =====
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize language display
            const initialSelectedLangOption = document.querySelector('.language-option .selected-check')?.closest('.language-option');
            if (initialSelectedLangOption && elements.selectedLanguageSpan) {
                const lang = initialSelectedLangOption.dataset.lang;
                const flagUrl = initialSelectedLangOption.dataset.flag;
                const name = initialSelectedLangOption.querySelector('span')?.textContent || lang;
                elements.selectedLanguageSpan.textContent = lang.toUpperCase();
                if (elements.languageFlagIcon) {
                    elements.languageFlagIcon.src = flagUrl;
                    elements.languageFlagIcon.alt = `${name} Flag`;
                }
            }
        });
        // ===== ACTIVE LINK HANDLER =====
const navLinks = document.querySelectorAll('.nav-menu .nav-link');

navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        // إذا كنت تريد منع تحديث الصفحة عند الضغط (للتجربة فقط) يمكنك تفعيل السطر التالي:
        // e.preventDefault(); 

        // 1. إزالة كلاس active من جميع الروابط
        navLinks.forEach(l => l.classList.remove('active'));

        // 2. إضافة كلاس active للرابط الذي تم ضغطه فقط
        this.classList.add('active');
    });
});


// --- التحديث المطلوب (Update) ---
const searchInput = document.querySelector('.full-screen-search-input');
const searchBtn = document.querySelector('.full-screen-search-icon');

function triggerSearch() {
    if (!searchInput) return;
    const query = searchInput.value.trim();
    if (query !== "") {
        // الاحترافية: التوجه لصفحة الفلتر مع مسح أي تصنيفات قديمة لضمان دقة البحث
        window.location.href = 'filter.php?search=' + encodeURIComponent(query);
    }
}

if (searchBtn) {
    searchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        triggerSearch();
    });
}

if (searchInput) {
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerSearch();
        }
    });
}
window.toggleWishlist = function(btn, e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }

    const productId = btn.getAttribute('data-product-id');
    if (!productId) return;

    // 1. فتح القائمة الجانبية فوراً
    if (typeof openWishlist === "function") openWishlist();

    // 2. تحديث الحالة البصرية فوراً لكل الأزرار التي تحمل نفس ID المنتج
    const isCurrentlyActive = btn.classList.contains('active');
// لكي يتعرف النظام على القلب في صفحة المنتج وفي الكروت معاً
const allHearts = document.querySelectorAll(`.wishlist-icon[data-product-id="${productId}"], .main-product-wishlist[data-product-id="${productId}"]`);    
    allHearts.forEach(heart => {
        if (isCurrentlyActive) {
            heart.classList.remove('active'); // إزالة اللون الأحمر والأيقونة الممتلئة
        } else {
            heart.classList.add('active');    // إضافة اللون الأحمر والأيقونة الممتلئة
        }
    });

    // 3. إرسال الطلب للسيرفر لتحديث قاعدة البيانات
    const params = new URLSearchParams();
    params.append('product_id', productId);

    fetch('wishlist-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(response => response.json())
    .then(data => {
         if (data.status === 'need_login') {
        window.location.href = 'register.php'; // توجيه المستخدم إذا لم يكن مسجلاً
        return;
    }
        if (data.status === 'success' || data.status === 'added' || data.status === 'removed') {
            // تحديث عداد الأرقام في الهيدر
            document.querySelectorAll('.wishlist-badge').forEach(badge => {
                badge.innerText = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            });

            // تحديث محتوى القائمة الجانبية بالبيانات الجديدة من السيرفر
            if (typeof updateWishlistSidebar === "function") {
                updateWishlistSidebar();
            }
        }
    })
    .catch(err => console.error('Error:', err));
};

// دالة تحديث محتوى القائمة الجانبية
window.updateWishlistSidebar = function(isAdding = false) {
    const container = document.getElementById('wishlist-items-container');
    const overlay = document.getElementById('overlay');
    const closeBtn = document.getElementById('close-wishlist');

    // قفل الضغط على الخلفية وزر الإغلاق أثناء التحميل
    if (overlay) overlay.style.setProperty('pointer-events', 'none', 'important');
    if (closeBtn) closeBtn.style.setProperty('pointer-events', 'none', 'important');
    
    if (container) {
        // إظهار الدائرة التي سيتم تحريكها بواسطة كود الـ CSS أعلاه
        container.innerHTML = `
            <div style="display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; padding: 120px 20px !important; width: 100% !important; text-align: center !important;">
                <div class="wishlist-spinner-circle"></div>
                <p style="margin-top: 25px !important; font-size: 14px !important; font-weight: 800 !important; color: #000000 !important; font-family: 'Cairo', sans-serif !important;">جاري تحديث المفضلة...</p>
            </div>`;
    }

    fetch('get-wishlist-items.php?v=' + Date.now())
    .then(response => response.text())
    .then(html => {
        if (container) {
            container.innerHTML = html;
        }
        
        // إعادة تفعيل التفاعل بعد التحميل
        if (overlay) overlay.style.setProperty('pointer-events', 'auto', 'important');
        if (closeBtn) closeBtn.style.setProperty('pointer-events', 'auto', 'important');
        
        if (isAdding && typeof openWishlist === "function") {
            openWishlist();
        }
    });
};
// متغير عالمي لحفظ التوكن وتحديثه
let currentCsrfToken = '<?php echo $_SESSION["csrf_token"]; ?>';
let isCartUpdating = false; // لمنع تداخل العمليات

window.updateCart = function(productId, action, newQty = 1, variationId = 0, attributes = '') {
    if (isCartUpdating) return; // منع النقر المتعدد السريع جداً
    if (action === 'update' && newQty < 1) { action = 'remove'; }

    isCartUpdating = true;
    showCartLoading(); // تعطيل الأزرار فوراً

    const params = new URLSearchParams();
    params.append('action', action);
    params.append('product_id', productId);
    params.append('variation_id', variationId);
    params.append('quantity', newQty);
    params.append('attributes', attributes);
    params.append('csrf_token', currentCsrfToken);

    fetch('cart-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(r => r.json())
    .then(res => {
        if (res.new_csrf) currentCsrfToken = res.new_csrf;

        if (res.status === 'success') {
            // تحديث السلة برمجياً دون انتظار طلب HTML منفصل لزيادة السرعة
            updateCartBadge(res.data.count);
            window.currentCartForWhatsApp = res.data; 
            // الآن نحدث الـ HTML ليبقى التصميم متزامناً
            refreshCartUI();
        } else {
            alert(res.message || "فشلت العملية");
            hideCartLoading();
            isCartUpdating = false;
        }
    })
    .catch(err => {
        console.error('Cart Error:', err);
        hideCartLoading();
        isCartUpdating = false;
    });
};

function updateTotalsFromAPI() {
    const params = new URLSearchParams();
    params.append('action', 'fetch');
    params.append('csrf_token', currentCsrfToken);

    fetch('cart-api.php', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(r => r.json())
    .then(res => {
        if (res.new_csrf) currentCsrfToken = res.new_csrf;
        
        if(res.status === 'success' && res.data) {
            const total = parseFloat(res.data.total).toFixed(2);
            document.getElementById('cart-total').innerText = total + ' د.م';
            updateCartBadge(res.data.count);
            window.currentCartForWhatsApp = res.data; 
            
            // تفعيل زر الواتساب فقط بعد اكتمال جلب البيانات والمجاميع
            hideCartLoading();
        }
        isCartUpdating = false;
    });
}

function refreshCartUI() {
    fetch('get-cart-html.php?v=' + Date.now())
    .then(r => r.text())
    .then(html => {
        const container = document.getElementById('cart-products-container');
        if (container) {
            container.innerHTML = html;
        }
        updateTotalsFromAPI(); // جلب المجاميع وتفعيل الأزرار
    });
}

function showCartLoading() {
    const waBtn = document.querySelector('.btn-whatsappp');
    if(waBtn) {
        waBtn.disabled = true;
        waBtn.style.opacity = '0.5';
        waBtn.innerHTML = '<span>جاري التحديث...</span>';
    }
}

function hideCartLoading() {
    const waBtn = document.querySelector('.btn-whatsappp');
    if(waBtn) {
        waBtn.disabled = false;
        waBtn.style.opacity = '1';
        waBtn.innerHTML = '<span style="font-weight:700;">اطلب عبر واتساب</span>';
    }
}

function updateCartBadge(count) {
    document.querySelectorAll('.cart-badge').forEach(b => {
        const totalCount = parseInt(count) || 0;
        b.innerText = totalCount;
        b.style.display = totalCount > 0 ? 'flex' : 'none';
    });
}

window.buyCartViaWhatsapp = function() {
    const data = window.currentCartForWhatsApp;
    if (!data || data.count === 0) return alert('السلة فارغة حالياً');
    
    let msg = `*طلب جديد من المتجر*\n\n`;
    data.items_list.forEach((item, i) => {
        let details = item.attr ? `\n   التفاصيل: ${item.attr}` : "";
        let link = item.link ? `\n   الرابط: ${item.link}` : ""; // إضافة الرابط هنا
        msg += `${i+1}. *${item.name}*${details}${link}\n   الكمية: ${item.qty} | السعر: ${item.price} د.م\n\n`;
    });
    msg += `--------------------------\n`;
    msg += `*الإجمالي النهائي:* ${data.total} د.م`;
    
    const phoneNumber = "<?php echo $whatsapp_number; ?>";
    window.open(`https://wa.me/${phoneNumber}?text=${encodeURIComponent(msg)}`, '_blank');
};

document.addEventListener('DOMContentLoaded', refreshCartUI);
window.addAllWishlistToCart = function() {
    const btn = document.querySelector('.wishlist-sidebar .btn-professional'); 
    if (!btn) return;

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>جاري النقل للسلة...</span>';

    fetch('wishlist-to-cart-bulk.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            
            // --- NEW: Reset all heart icons on the product grid ---
            document.querySelectorAll('.wishlist-icon, .main-product-wishlist').forEach(icon => {
                icon.classList.remove('active');
            });

            // 1. Update Wishlist Sidebar (will now show as empty)
            if (typeof updateWishlistSidebar === "function") {
                updateWishlistSidebar();
            }

            // 2. Refresh Cart UI (to show new items)
            if (typeof refreshCartUI === "function") {
                refreshCartUI();
            }

            // 3. Clear Wishlist Badges globally
            document.querySelectorAll('.wishlist-badge').forEach(badge => {
                badge.innerText = '0';
                badge.style.display = 'none';
            });
            
            // 4. Smooth transition: Close wishlist and open cart
            setTimeout(() => {
                closeAllMenus(); 
                setTimeout(() => {
                    if (typeof openCart === "function") openCart();
                }, 400);
            }, 500);

        } else if (data.status === 'empty') {
            alert('قائمة المفضلة فارغة بالفعل');
        }
    })
    .catch(err => {
        console.error('Error:', err);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
};
    </script>
