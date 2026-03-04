# Techx.life - منصة الاستقطاع المالي الذكية

هذا المشروع هو نظام متكامل لإدارة الاستقطاعات المالية من الرواتب والحسابات البنكية، يربط الأفراد (عبر النفاذ الوطني) والمنشآت (عبر توثيق) مع الجهات المستفيدة.

## المميزات
- تسجيل دخول آمن عبر النفاذ الوطني وتوثيق
- ربط حسابات بنكية عبر Open Banking (Lean)
- إنشاء عقود واستقطاعات شهرية
- لوحات تحكم متعددة (عميل، منشأة، مشرف)
- تقارير PDF شاملة
- واجهة API للتكامل الخارجي

## التقنيات المستخدمة
- Laravel 11
- MySQL
- Bootstrap 5
- Dompdf
- Lean Open Banking API

## متطلبات التشغيل
- PHP 8.2+
- Composer
- MySQL
- مفاتيح API من: نفاذ، توثيق، Lean

## التثبيت
```bash
git clone https://github.com/Iahiax/techxx.git
cd techxx
composer install
cp .env.example .env
# قم بتعديل ملف .env
php artisan key:generate
php artisan migrate
php artisan serve




# techx.life
composer create-project laravel/laravel techx.life
cd techx.life

إعداد الحزم الإضافية:

composer require laravel/socialite (لتسهيل OAuth).

composer require barryvdh/laravel-dompdf (لإنشاء PDF).

composer require guzzlehttp/guzzle (للاستعلامات API).

composer require lean/php-sdk (إذا وُجدت حزمة رسمية من Lean).


يتطلب هذا الملف وجود قوالب Blade خاصة بملفات PDF في resources/views/pdf/admin/ مثل:

users_report.blade.php

organizations_report.blade.php

contracts_report.blade.php

deductions_report.blade.php

revenue_report.blade.php

financial_statement.blade.php

يتم استخدام مكتبة barryvdh/laravel-dompdf لتحميل PDF. تأكد من تثبيتها عبر composer.

جميع الدوال تستقبل معاملات فلترة من الـ request لتحديد نطاق التقرير.
