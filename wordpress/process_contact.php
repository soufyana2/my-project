<?php
// منع أي مخرجات نصية تفسد الـ JSON
ob_start();
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="public/images/favicon.svg">
  <title>Contact Processing</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body>
        <?php include 'header.php'; ?>

        <main class="max-w-3xl mx-auto px-4 py-16">
            <h1 class="text-2xl font-bold mb-4">Contact Processing</h1>
            <p class="text-gray-600">This endpoint handles contact form submissions.</p>
        </main>

        <?php include 'footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: application/json');

try {
    // تحديد المسار الرئيسي لمجلد projects
    // بما أننا داخل projects/contact، سنصعد درجة واحدة للأعلى
    $basePath = realpath(__DIR__ . '/..'); 

    // استدعاء ملفات المشروع الأساسية باستخدام المسار الحقيقي
    if (!file_exists($basePath . '/db.php')) throw new Exception("الملف db.php غير موجود في $basePath");
    if (!file_exists($basePath . '/functions.php')) throw new Exception("الملف functions.php غير موجود في $basePath");
    if (!file_exists($basePath . '/vendor/autoload.php')) throw new Exception("مجلد vendor غير موجود. قم بتشغيل composer install");

    require_once $basePath . '/db.php';
    require_once $basePath . '/functions.php';
    require_once $basePath . '/vendor/autoload.php';

    // تحميل إعدادات البريد
    if (file_exists($basePath . '/keys.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($basePath, 'keys.env');
        $dotenv->load();
    }

    // التحقق من CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("خطأ في التحقق من الأمان (CSRF). يرجى تحديث الصفحة.");
    }

    // التحقق من البريد والرسالة
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $msg_content = htmlspecialchars($_POST['message'] ?? '');
    $f_name = htmlspecialchars($_POST['first_name'] ?? '');
    $l_name = htmlspecialchars($_POST['last_name'] ?? '');

    if (!$email || empty($msg_content)) {
        throw new Exception("يرجى إدخال بريد إلكتروني صحيح ورسالة.");
    }

    // حماية Brute Force (تأكد أن الدالة getClientIP موجودة في functions.php)
    $ip = getClientIP(); 
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() > 5) {
        throw new Exception("لقد أرسلت الكثير من الرسائل. يرجى الانتظار ساعة.");
    }

    // إعداد PHPMailer باستخدام الـ Namespace الكامل لتجنب خطأ VS Code
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USERNAME'];
    $mail->Password   = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = (int)$_ENV['SMTP_PORT'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Abdelwahab Accessories');
    $mail->addAddress($_ENV['SMTP_USERNAME']);
    $mail->addReplyTo($email, $f_name);

    $mail->isHTML(true);
    $mail->Subject = "Contact Form: $f_name $l_name";
    $mail->Body    = "<h3>رسالة جديدة من الموقع</h3><p><b>المرسل:</b> $f_name $l_name</p><p><b>البريد:</b> $email</p><p><b>الرسالة:</b><br>$msg_content</p>";

    $mail->send();

    // تسجيل المحاولة في قاعدة البيانات
    $ins = $pdo->prepare("INSERT INTO contact_attempts (ip_address, email) VALUES (?, ?)");
    $ins->execute([$ip, $email]);

    ob_clean(); // تنظيف أي مخرجات غير مقصودة
    echo json_encode(['status' => 'success', 'message' => 'تم إرسال رسالتك بنجاح!']);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
