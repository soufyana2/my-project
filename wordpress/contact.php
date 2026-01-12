<?php
session_start();
// توليد توكن الأمان CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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
  <title>اتصل بنا | Abdelwahab Accessories</title>
  <meta name="description" content="تواصل مع Abdelwahab Accessories لاستفساراتكم حول الجلابة المغربية والإكسسوارات.">

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
      padding-top: var(--header-offset, 0px) !important;
      direction: rtl !important;
    }

    /* سيكشن شفاف ليظهر لون خلفية الجسم */
    .ms-section-transparent {
      background-color: transparent !important;
    }

    /* أزرار الإرسال - التصميم المطلوب */
    .ms-btn-submit {
      background-color: transparent !important;
      color: #000000 !important;
      border: 2px solid #000000 !important;
      transition: all 0.4s ease !important;
      position: relative !important;
      overflow: hidden !important;
      border-radius: 4px !important;
      cursor: pointer !important;
    }

    @media (min-width: 1024px) {
      .ms-btn-submit:hover {
        color: #C8A95A !important;
        border-color: #C8A95A !important;
        background-color: transparent !important;
      }
    }

    /* تنسيق الحقول */
    .ms-form-field {
      width: 100% !important;
      padding: 12px 15px !important;
      border: 1px solid var(--ms-border) !important;
      border-radius: 0px !important;
      outline: none !important;
      direction: rtl !important;
      text-align: right !important;
      background: #ffffff !important;
      transition: border-color 0.3s !important;
    }

    .ms-form-field:focus {
      border-color: var(--ms-gold) !important;
    }

    /* الأسئلة الشائعة - الحركة الاحترافية */
    .ms-faq-item {
      border: 1px solid #f0f0f0 !important;
      margin-bottom: 10px !important;
      background: transparent !important;
    }

    .ms-faq-button {
      width: 100% !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      padding: 18px !important;
      background: transparent !important;
      border: none !important;
      cursor: pointer !important;
    }

    .ms-faq-answer {
      display: grid !important;
      grid-template-rows: 0fr !important;
      transition: grid-template-rows 0.4s ease-out, padding 0.4s ease !important;
      overflow: hidden !important;
      padding: 0 18px !important;
      text-align: right !important;
    }

    .ms-faq-answer > div {
      min-height: 0 !important;
      opacity: 0 !important;
      transition: opacity 0.3s ease !important;
    }

    .ms-faq-item.ms-active .ms-faq-answer {
      grid-template-rows: 1fr !important;
      padding: 10px 18px 20px !important;
    }

    .ms-faq-item.ms-active .ms-faq-answer > div {
      opacity: 1 !important;
    }

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

    .ms-dot-black {
      width: 8px !important;
      height: 8px !important;
      background-color: #000000 !important;
      border-radius: 50% !important;
    }

    /* تقليل الفراغات في الموبايل */
    @media (max-width: 768px) {
      .ms-mobile-py { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
      .ms-mobile-mb { margin-bottom: 1rem !important; }
    }
  </style>
</head>

<body>

<?php include 'header.php'; ?>

  <!-- Header -->
  <header class="ms-section-transparent py-8 ms-mobile-py ms-reveal">
    <div class="container mx-auto px-4 text-center">
      <h1 class="text-xl md:text-2xl font-black text-black mb-3">تواصل <span style="color:var(--ms-gold) !important;">معنا</span></h1>
      <div class="w-16 h-1 bg-black mx-auto"></div>
    </div>
  </header>

  <!-- Main Content -->
  <section class="ms-section-transparent py-8 ms-mobile-py">
    <div class="container mx-auto px-4">
      <div class="flex flex-col lg:flex-row gap-10 items-stretch justify-center max-w-6xl mx-auto">
        
        <!-- Form Section -->
        <div class="w-full lg:w-5/12 ms-reveal">
          <div class="h-full flex flex-col">
            <h2 class="text-lg font-bold text-center mb-6 text-black ms-mobile-mb">أرسل لنا رسالة</h2>
            
            <form id="ms-contact-form" class="space-y-4 flex-grow">
              <!-- التوكن الخفي للحماية -->
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="first_name" placeholder="الاسم الأول" class="ms-form-field" required>
                <input type="text" name="last_name" placeholder="الاسم العائلي" class="ms-form-field" required>
              </div>

              <input type="email" name="email" placeholder="البريد الإلكتروني" class="ms-form-field" required>

              <textarea name="message" placeholder="كيف يمكننا مساعدتك؟" class="ms-form-field" style="height: 310px !important;" required></textarea>

              <button type="submit" id="ms-submit-btn" class="ms-btn-submit w-full py-4 text-lg font-bold">
                <span class="flex items-center justify-center">
                    <span id="button-text">إرسال الطلب</span>
                    <i id="button-icon" class="fas fa-paper-plane mr-2"></i>
                    <div id="button-loader" class="hidden mr-2">
                        <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- Image & Info Section -->
        <div class="w-full lg:w-5/12 space-y-8 ms-reveal">
          <div class="overflow-hidden border border-gray-100" style="height: 450px !important;">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1768252470/logo_dw0woa.png" 
                 alt="Contact logo" 
                 class="w-full h-full object-cover" 
                 loading="lazy">
          </div>

          <div class="border-t border-b border-gray-100 py-6">
            <h3 class="text-lg font-bold mb-4 text-black">لماذا تختار متجرنا؟</h3>
            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <div class="ms-dot-black"></div>
                <span class="text-gray-700 text-sm md:text-base">تصاميم جلابة مغربية أصيلة 100%</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="ms-dot-black"></div>
                <span class="text-gray-700 text-sm md:text-base">شحن سريع وآمن لجميع المدن</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="ms-dot-black"></div>
                <span class="text-gray-700 text-sm md:text-base">دعم متواصل عبر الواتساب</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="ms-section-transparent py-12">
    <div class="container mx-auto px-4 max-w-4xl">
      <div class="text-center mb-8 ms-reveal">
        <h2 class="text-2xl font-bold text-black mb-2">الأسئلة الشائعة</h2>
        <p class="text-gray-500 text-sm">كل ما تود معرفته عن خدماتنا</p>
      </div>

      <div class="space-y-3 ms-reveal">
        <div class="ms-faq-item">
          <button class="ms-faq-button" onclick="toggleFaq(this)">
            <span class="font-bold text-gray-800">كم يستغرق توصيل الطلبية؟</span>
            <i class="fas fa-plus text-xs text-gray-400"></i>
          </button>
          <div class="ms-faq-answer">
            <div class="text-gray-600 text-sm">تستغرق مدة التوصيل من 3 إلى 4 أيام عمل كحد أقصى.</div>
          </div>
        </div>

        <div class="ms-faq-item">
          <button class="ms-faq-button" onclick="toggleFaq(this)">
            <span class="font-bold text-gray-800">ما هي سياسة الاسترجاع؟</span>
            <i class="fas fa-plus text-xs text-gray-400"></i>
          </button>
          <div class="ms-faq-answer">
            <div class="text-gray-600 text-sm">يتم الاسترجاع في غضون يومين من الاستلام بشرط أن يكون المنتج في حالته الأصلية السليمة.</div>
          </div>
        </div>

        <div class="ms-faq-item">
          <button class="ms-faq-button" onclick="toggleFaq(this)">
            <span class="font-bold text-gray-800">كيف يمكنني الدفع؟</span>
            <i class="fas fa-plus text-xs text-gray-400"></i>
          </button>
          <div class="ms-faq-answer">
            <div class="text-gray-600 text-sm">نعتمد نظام الدفع عند الاستلام، ويتم تأكيد الطلب عبر الواتساب.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script>
    // وظيفة الأسئلة الشائعة
    function toggleFaq(btn) {
      const item = btn.parentElement;
      const icon = btn.querySelector('i');
      
      document.querySelectorAll('.ms-faq-item').forEach(el => {
        if (el !== item) {
          el.classList.remove('ms-active');
          el.querySelector('i').classList.replace('fa-minus', 'fa-plus');
        }
      });

      if (item.classList.contains('ms-active')) {
        item.classList.remove('ms-active');
        icon.classList.replace('fa-minus', 'fa-plus');
      } else {
        item.classList.add('ms-active');
        icon.classList.replace('fa-plus', 'fa-minus');
      }
    }

    // وظيفة التحريك عند السكرول
    function reveal() {
      document.querySelectorAll(".ms-reveal").forEach(el => {
        if (el.getBoundingClientRect().top < window.innerHeight - 50) {
          el.classList.add("ms-active");
        }
      });
    }

    // برمجة الإرسال (AJAX) - الربط مع Backend
    document.addEventListener('DOMContentLoaded', function () {
      function offsetForHeader() {
        const header = document.querySelector('.header-modern');
        if (!header) return;
        document.documentElement.style.setProperty('--header-offset', `${header.offsetHeight}px`);
      }

      offsetForHeader();
      window.addEventListener('resize', offsetForHeader);
      window.addEventListener('load', offsetForHeader);

      reveal();
      window.addEventListener("scroll", reveal);

      const form = document.getElementById('ms-contact-form');
      form.addEventListener('submit', async function (e) {
        e.preventDefault(); // منع إعادة تحميل الصفحة أو ظهور #

        const btn = document.getElementById('ms-submit-btn');
        const btnText = document.getElementById('button-text');
        const btnIcon = document.getElementById('button-icon');
        const btnLoader = document.getElementById('button-loader');

        // حالة التحميل
        btn.disabled = true;
        btnText.textContent = 'جاري الإرسال...';
        btnIcon.classList.add('hidden');
        btnLoader.classList.remove('hidden');

        try {
          const response = await fetch('process_contact.php', {
            method: 'POST',
            body: new FormData(this)
          });

          const result = await response.json();
          alert(result.message);
          if (result.status === 'success') form.reset();

        } catch (error) {
          console.error(error);
          alert("حدث خطأ في الاتصال بالسيرفر، يرجى المحاولة لاحقاً.");
        } finally {
          // إنهاء التحميل
          btn.disabled = false;
          btnText.textContent = 'إرسال الطلب';
          btnIcon.classList.remove('hidden');
          btnLoader.classList.add('hidden');
        }
      });
    });
  </script>

</body>
</html>
