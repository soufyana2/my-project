<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>يرجى الانتظار | Abdolwahab Accessories</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* General Settings */
        body { 
            font-family: 'Cairo', sans-serif;
            background-color: #ffffff; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            color: #111;
        }
        
        .font-logo { font-family: 'Playfair Display', serif; }
        .text-gold { color: #C8A95A; }
        .border-gold { border-color: #C8A95A; }
        
        /* Header Styles */
        .page-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        @media (min-width: 768px) {
            .page-header { justify-content: flex-end; }
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            direction: ltr; 
        }
        .logo-img { height: 60px; width: auto; }
        .logo-text-group { display: flex; flex-direction: column; color: #000; }
        .logo-main { font-size: 1.5rem; font-weight: 700; line-height: 1; letter-spacing: -0.02em; }
        .logo-sub { font-size: 0.65rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.2em; color: #555; margin-top: 3px; }

        /* --- VISITOR LINK STYLES --- */
        
        /* Base Link Styling */
        .visitor-link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 320px;
            padding: 18px 24px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 0; /* Sharp edges for luxury feel */
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            overflow: hidden;
            margin: 0 auto;
            letter-spacing: 0.05em;
        }

        /* STATE 1: DISABLED (Waiting) */
        .state-disabled {
            background-color: #f3f3f3;
            color: #999;
            border: 1px solid #e5e5e5;
            pointer-events: none; /* Make unclickable */
            cursor: wait;
        }

        /* STATE 2: ACTIVE (Ready) */
        .state-active {
            background-color: #000;
            color: #fff;
            border: 1px solid #000;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3);
        }

        /* Shine Effect on Hover (Only for Active) */
        .state-active::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(200, 169, 90, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        @media (min-width: 1024px) {
            .state-active:hover::before {
                left: 100%;
            }

            .state-active:hover {
                background-color: #111;
                transform: translateY(-2px);
                border-color: #C8A95A; /* Slight gold border on hover */
            }
        }

        /* Icon Animation */
        .icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            transition: transform 0.3s ease;
        }
        
        @media (min-width: 1024px) {
            .state-active:hover .icon-box {
                transform: translateX(-5px);
            }
        }

        /* Fade Animation */
        .fade-in {
            opacity: 0;
            animation: fadeIn 1s ease-out forwards;
        }
        @keyframes fadeIn { to { opacity: 1; } }

    </style>
</head>
<body>

    <!-- Header -->
    <header class="page-header">
        <div class="logo-container">
            <img src="https://res.cloudinary.com/dmakzfsc4/image/upload/f_webp/v1764704473/logoeraser_f2puji.png" alt="Abdolwahab Logo" class="logo-img">
            <div class="logo-text-group font-logo">
                <span class="logo-main">Abdolwahab</span>
                <span class="logo-sub">PARFUMS & ACCESSORIES</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full max-w-lg px-6 text-center fade-in mt-12">
        
        <!-- Elegant Divider -->
        <div class="w-16 h-px bg-gray-200 mx-auto mb-8"></div>

        <!-- Title -->
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 leading-tight">
            نعتذر، الخط مشغول
        </h1>

        <!-- Description -->
        <p class="text-gray-500 text-sm md:text-base leading-relaxed mb-10 font-light px-4">
            نواجه حالياً عدداً كبيراً من الزوار. لضمان جودة الخدمة، يرجى الانتظار للحظات.
            <br class="hidden sm:block">
            سيظهر رابط الدخول أدناه بمجرد توفر الإمكانية.
        </p>

        <!-- Timer Section -->
        <div class="mb-12">
            <div class="relative inline-block">
                <!-- Decorative Circle behind timer -->
                <div class="absolute inset-0 bg-[#C8A95A]/5 rounded-full blur-xl transform scale-150"></div>
                
                <div class="relative text-7xl font-logo font-bold text-[#C8A95A] tracking-widest tabular-nums leading-none" id="timer">
                    00:30
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4 uppercase tracking-[0.2em] font-logo">Seconds Remaining</p>
        </div>

        <!-- THE VISITOR LINK (Anchor Tag) -->
        <!-- Starts with href="#" and class 'state-disabled' -->
        <a id="visitor-link" href="#" class="visitor-link state-disabled group">
            
            <!-- Icon -->
            <span class="icon-box">
                <!-- Wait Icon (Clock) -->
                <svg id="icon-svg" class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>

            <!-- Text -->
            <span id="link-text">يرجى الانتظار...</span>
        </a>

        <!-- Footer System ID -->
        <div class="mt-16 border-t border-gray-100 pt-6">
            <p class="text-[10px] text-gray-300 font-logo uppercase tracking-[0.2em]">
                Rate Limit Protection • ID: 429
            </p>
        </div>

    </main>

    <script>
        // --- Configuration ---
        const TARGET_URL = "index.php?v=visitor"; // The link destination
        let timeLeft = 30; // Seconds
        
        // DOM Elements
        const timerDisplay = document.getElementById('timer');
        const linkElement = document.getElementById('visitor-link');
        const linkText = document.getElementById('link-text');
        const iconSvg = document.getElementById('icon-svg');

        // --- Countdown Logic ---
        const countdown = setInterval(() => {
            timeLeft--;
            
            // Format Time (00:09)
            const formattedTime = timeLeft < 10 ? `00:0${timeLeft}` : `00:${timeLeft}`;
            timerDisplay.textContent = formattedTime;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                activateLink();
            }
        }, 1000);

        // --- Activate Link Function ---
        function activateLink() {
            // 1. Set the real URL
            linkElement.href = TARGET_URL;
            
            // 2. Change Visual State (Remove disabled, add active)
            linkElement.classList.remove('state-disabled');
            linkElement.classList.add('state-active');
            
            // 3. Update Text
            linkText.textContent = "الدخول كزائر الآن";
            
            // 4. Update Icon (Arrow/Door)
            iconSvg.classList.remove('animate-pulse'); // Stop pulsing
            // Replace SVG path with a "Login/Enter" arrow
            iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>';

            // 5. Visual Tweak on Timer to show it's done (Turn Black)
            timerDisplay.classList.remove('text-[#C8A95A]');
            timerDisplay.classList.add('text-black');
        }
    </script>
</body>
</html>
