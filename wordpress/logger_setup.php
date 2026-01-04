<?php
// تأكد من تحميل مكتبة Composer
// إذا كان مجلد vendor في مسار مختلف، قم بتعديل المسار هنا
require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\UidProcessor;

/**
 * دالة الحصول على الـ Logger
 * 
 * هذه الدالة تطبق نمط Singleton لضمان عدم إنشاء كائنات متعددة لنفس القناة
 * في نفس الطلب، مما يحسن الأداء.
 * 
 * @param string $channel اسم القناة (auth, security, database, etc...)
 * @return Logger
 */
function getLogger(string $channel): Logger {
    // مصفوفة ثابتة لتخزين الـ Loggers التي تم إنشاؤها مسبقاً في الذاكرة
    static $loggers = [];

    // إذا تم إنشاء الـ Logger لهذه القناة من قبل، أعد استخدامه
    if (isset($loggers[$channel])) {
        return $loggers[$channel];
    }

    // إعداد مسار مجلد السجلات
    $logDir = __DIR__ . '/logs';
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // إنشاء كائن Logger جديد
    $logger = new Logger($channel);

    // تنسيق السجل (التاريخ - القناة - المستوى - الرسالة - البيانات)
    // "Y-m-d H:i:s" هو تنسيق الوقت، و allowInlineLineBreaks يسمح بأسطر متعددة للبيانات
    $dateFormat = "Y-m-d H:i:s";
    $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
    $formatter = new LineFormatter($output, $dateFormat, true, true);

    // تحديد اسم الملف بناءً على القناة لتنظيم الملفات
    // سيتم استخدام RotatingFileHandler لإنشاء ملف جديد كل يوم (app-2023-12-09.log)
    switch ($channel) {
        case 'security':
            // سجلات الأمان والحظر ومحاولات الاختراق توضع في ملف منفصل
            $filename = 'security.log';
            $level = Logger::DEBUG; // تسجيل كل شيء للأمان
            break;

        case 'database':
            // أخطاء قاعدة البيانات
            $filename = 'database_errors.log';
            $level = Logger::ERROR; // تسجيل الأخطاء فقط
            break;

        case 'auth':
        case 'otp':
            // عمليات الدخول والتسجيل
            $filename = 'auth.log';
            $level = Logger::INFO;
            break;

        case 'ratelimit':
            // سجلات تجاوز الحد المسموح
            $filename = 'ratelimit.log';
            $level = Logger::NOTICE;
            break;

        default:
            // أي قناة أخرى (مثل general, geoip) تذهب للملف العام
            $filename = 'app.log';
            $level = Logger::INFO;
            break;
    }

    // إعداد المعالج (Handler)
    // RotatingFileHandler: يقوم بتدوير الملفات يومياً وحذف الملفات الأقدم من 30 يوماً
    $handler = new RotatingFileHandler("$logDir/$filename", 30, $level);
    $handler->setFormatter($formatter);
    
    // إضافة المعالج للوجر
    $logger->pushHandler($handler);

    // --- معالجات إضافية (Processors) ---
    
    // WebProcessor: يضيف تلقائياً معلومات الطلب (URI, Method, IP) للسجل
    // ملاحظة: الكود الخاص بك يضيف IP يدوياً في الـ context، ولكن هذا يضيفه بشكل قياسي أيضاً
    $logger->pushProcessor(new WebProcessor());

    // UidProcessor: يضيف معرف فريد (Unique ID) لكل طلب
    // مفيد جداً لتتبع خطوات المستخدم في نفس الطلب عبر سجلات مختلفة
    $logger->pushProcessor(new UidProcessor());

    // تخزين الـ Logger في المصفوفة لاستخدامه لاحقاً
    $loggers[$channel] = $logger;

    return $logger;
}