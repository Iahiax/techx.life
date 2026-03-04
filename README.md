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
