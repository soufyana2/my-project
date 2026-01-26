<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Preconnect & SEO -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="icon" type="image/svg+xml" href="public/images/favicon.svg">
  <title>من نحن | اكسسوارات عبدالوهاب</title>

  <!-- Tailwind & Fonts -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --ms-gold: #C8A95A !important;
      --ms-black: #000000 !important;
      --ms-border: #e2e8f0 !important;
    }

    body {
      background-color: #ffffff !important;
      font-family: 'Cairo', sans-serif !important;
      margin: 0 !important;
      padding: 0 !important;
      /* هذا المتغير سيتم حسابه بواسطة JS ليعطي مساحة للهيدر */
      padding-top: var(--header-offset, 80px) !important; 
      direction: rtl !important;
    }

    .ms-section-transparent { background-color: transparent !important; }

    /* أزرار التواصل */
    .ms-btn-contact {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      border: 2px solid #000000 !important;
      transition: all 0.4s ease !important;
      border-radius: 4px !important;
      padding: 12px 20px;
      font-weight: bold;
    }

    @media (min-width: 1024px) {
      .ms-btn-contact:hover {
        color: var(--ms-gold) !important;
        border-color: var(--ms-gold) !important;
      }
    }

    .ms-instagram-btn { background-color: #000 !important; color: #fff !important; }

    /* تأثيرات الظهور */
    .ms-reveal {
      opacity: 0 !important;
      transform: translateY(20px) !important;
      transition: all 0.8s ease !important;
    }

    .ms-reveal.ms-active {
      opacity: 1 !important;
      transform: translateY(0) !important;
    }

    .ms-dot-gold {
      width: 8px !important;
      height: 8px !important;
      background-color: var(--ms-gold) !important;
      border-radius: 50% !important;
    }

    .ms-about-img-container {
        position: relative;
        border: 1px solid var(--ms-border);
    }
    
    .ms-about-img-container::after {
        content: '';
        position: absolute;
        top: 15px;
        right: -15px;
        width: 100%;
        height: 100%;
        border: 2px solid var(--ms-gold);
        z-index: -1;
    }

    @media (max-width: 768px) {
      .ms-about-img-container::after { display: none; }
      /* زيادة المسافة في الموبايل لضمان عدم التداخل */
      body { padding-top: 100px !important; }
    }
  </style>
</head>

<body>

<?php include 'header.php'; ?>

  <!-- Hero Section (تم زيادة padding-top هنا كحل إضافي) -->
  <header class="ms-section-transparent pt-16 pb-12 ms-reveal">
    <div class="container mx-auto px-4 text-center">
      <h1 class="text-xl md:text-2xl font-black text-black mb-3">من هو <span style="color:var(--ms-gold) !important;">اكسسوارات عبدالوهاب؟</span></h1>
      <div class="w-24 h-1 bg-black mx-auto"></div>
    </div>
  </header>

  <!-- About Content Section -->
  <section class="ms-section-transparent py-12">
    <div class="container mx-auto px-4">
      <div class="flex flex-col lg:flex-row gap-16 items-center max-w-6xl mx-auto">
        
        <!-- Image Section -->
        <div class="w-full lg:w-1/2 ms-reveal">
          <div class="ms-about-img-container">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png" 
                 alt="Abdelwahab Accessories" 
                 class="w-full h-auto object-cover bg-white" 
                 loading="lazy">
          </div>
        </div>

        <!-- Text Section -->
        <div class="w-full lg:w-1/2 space-y-6 ms-reveal">
          <h2 class="text-2xl font-bold text-black border-r-4 pr-4" style="border-right-color: var(--ms-gold);">قصتنا وأهدافنا</h2>
          <p class="text-gray-700 leading-relaxed text-lg text-justify">
            مرحباً بكم في **اكسسوارات عبدالوهاب**، وجهتكم الأولى للأناقة والجمال. نحن نفخر بتقديم تشكيلة فريدة من نوعها من **الساعات الراقية، العطور الفواحة، والإكسسوارات** التي تم اختيارها بعناية لتناسب جميع الأذواق والمناسبات.
          </p>
          
          <div class="space-y-4 py-4">
            <div class="flex items-start gap-3">
              <div class="ms-dot-gold mt-2"></div>
              <p class="text-gray-700"><strong>فخامة الاختيار:</strong> منتجاتنا مختارة لتمنحك شعوراً بالتميز والرفاهية.</p>
            </div>
            <div class="flex items-start gap-3">
              <div class="ms-dot-gold mt-2"></div>
              <p class="text-gray-700"><strong>توصيل سريع:</strong> نضمن وصول مشترياتكم إلى باب منزلكم بأمان وسرعة.</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Contact Grid -->
  <section class="bg-gray-50 py-16">
    <div class="container mx-auto px-4 max-w-5xl">
      <div class="text-center mb-12 ms-reveal">
        <h2 class="text-2xl font-bold text-black mb-2">تواصلوا معنا مباشرة</h2>
        <p class="text-gray-500">نحن هنا للإجابة على جميع استفساراتكم</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 ms-reveal">
        <a href="https://instagram.com/YourUsername" target="_blank" class="ms-btn-contact ms-instagram-btn">
          <i class="fab fa-instagram text-xl"></i>
          <span>تابعنا على إنستغرام</span>
        </a>

      <a href="https://wa.me/212634229259" target="_blank" class="ms-btn-contact">
  <i class="fab fa-whatsapp text-xl"></i>
  <span>تواصل معنا عبر واتساب</span>
</a>


        <a href="mailto:contact@abdelwahab.com" class="ms-btn-contact">
          <i class="fas fa-envelope text-xl"></i>
          <span>البريد الإلكتروني</span>
        </a>

        <div class="ms-btn-contact cursor-default">
          <i class="fas fa-map-marker-alt text-xl"></i>
          <span>الموقع: المغرب، [تطوان]</span>
        </div>
      </div>
    </div>
  </section>

<?php include 'footer.php'; ?>

  <script>
    // وظيفة لحساب طول الهيدر وتعديل المسافة العلوية تلقائياً
    function offsetForHeader() {
      const header = document.querySelector('header') || document.querySelector('.header-modern');
      if (header) {
        const headerHeight = header.offsetHeight;
        document.documentElement.style.setProperty('--header-offset', headerHeight + 'px');
      }
    }

    function reveal() {
      document.querySelectorAll(".ms-reveal").forEach(el => {
        if (el.getBoundingClientRect().top < window.innerHeight - 50) {
          el.classList.add("ms-active");
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      offsetForHeader();
      reveal();
      window.addEventListener("scroll", reveal);
      window.addEventListener("resize", offsetForHeader);
    });
  </script>

</body>
</html>