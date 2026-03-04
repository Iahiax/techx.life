<?php

return [

    /*
    |--------------------------------------------------------------------------
    | اسم التطبيق
    |--------------------------------------------------------------------------
    |
    | هذا القيمة هي اسم تطبيقك. تُستخدم عندما يحتاج الإطار إلى وضع اسم التطبيق
    | في إشعار أو أي موقع آخر يتطلبه التطبيق أو حزمه.
    |
    */

    'name' => env('APP_NAME', 'techx.life'),

    /*
    |--------------------------------------------------------------------------
    | بيئة التطبيق
    |--------------------------------------------------------------------------
    |
    | هذه القيمة تحدد "البيئة" التي يعمل فيها تطبيقك حاليًا.
    | قد تحدد كيفية تفضيل تكوين الخدمات المختلفة التي يستخدمها التطبيق.
    | قم بتعيين هذا في ملف ".env" الخاص بك.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | وضع تصحيح الأخطاء
    |--------------------------------------------------------------------------
    |
    | عندما يكون تطبيقك في وضع التصحيح، سيتم عرض رسائل خطأ مفصلة مع
    | تتبع الأخطاء على كل خطأ يحدث داخل تطبيقك. إذا تم تعطيله،
    | سيتم عرض صفحة خطأ عامة بسيطة.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | رابط التطبيق
    |--------------------------------------------------------------------------
    |
    | يستخدم هذا الرابط بواسطة وحدة التحكم لتوليد روابط بشكل صحيح عند استخدام
    | أداة سطر الأوامر Artisan. يجب عليك تعيين هذا إلى الجذر
    | لتطبيقك بحيث يتم استخدامه عند تشغيل مهام Artisan.
    |
    */

    'url' => env('APP_URL', 'https://techx.life'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | المنطقة الزمنية للتطبيق
    |--------------------------------------------------------------------------
    |
    | هنا يمكنك تحديد المنطقة الزمنية الافتراضية لتطبيقك، والتي ستستخدمها
    | دوال PHP للتاريخ والوقت. قمنا بتعيين قيمة معقولة افتراضية لك.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Riyadh'),

    /*
    |--------------------------------------------------------------------------
    | إعدادات اللغة المحلية للتطبيق
    |--------------------------------------------------------------------------
    |
    | تحدد اللغة المحلية للتطبيق اللغة الافتراضية التي ستستخدمها
    | مزود خدمة الترجمة. أنت حر في تعيين هذه القيمة
    | إلى أي من اللغات المدعومة من التطبيق.
    |
    */

    'locale' => env('APP_LOCALE', 'ar'),

    /*
    |--------------------------------------------------------------------------
    | اللغة المحلية البديلة للتطبيق
    |--------------------------------------------------------------------------
    |
    | اللغة البديلة تحدد اللغة التي سيتم استخدامها عندما تكون اللغة الحالية
    | غير متوفرة. يمكنك تغيير القيمة لتتوافق مع أي من مجلدات اللغة
    | المتوفرة من خلال تطبيقك.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | لغة Faker
    |--------------------------------------------------------------------------
    |
    | سيتم استخدام هذه اللغة بواسطة مكتبة Faker PHP عند توليد بيانات
    | وهمية لقاعدة البيانات. على سبيل المثال، سيتم استخدام هذا للحصول على
    | أرقام هواتف محلية، معلومات عناوين الشوارع والمزيد.
    |
    */

    'faker_locale' => env('APP_FAKER_LOCALE', 'ar_SA'),

    /*
    |--------------------------------------------------------------------------
    | مفتاح التشفير
    |--------------------------------------------------------------------------
    |
    | يستخدم هذا المفتاح بواسطة خدمات التشفير في Laravel ويجب تعيينه
    | إلى سلسلة عشوائية طويلة من 32 حرفًا لضمان أن جميع القيم المشفرة
    | آمنة. يجب عليك فعل ذلك قبل نشر التطبيق.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | وضع الصيانة
    |--------------------------------------------------------------------------
    |
    | تحدد خيارات التكوين هذه السائق المستخدم لتحديد وإدارة
    | حالة "وضع الصيانة" في Laravel. سائق "cache" سيسمح
    | بالتحكم في وضع الصيانة عبر عدة عمليات.
    |
    | السائقون المدعومون: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | مزودي الخدمة المحملة تلقائياً
    |--------------------------------------------------------------------------
    |
    | مزودو الخدمة المدرجون هنا سيتم تحميلهم تلقائياً عند
    | طلب تطبيقك. لا تتردد في إضافة خدماتك الخاصة
    | إلى هذه المصفوفة لتوسيع الوظائف.
    |
    */

    'providers' => [
        /*
         * مزودو خدمات إطار Laravel...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * مزودو خدمات الحزم...
         */
        Barryvdh\Dompdf\ServiceProvider::class,

        /*
         * مزودو خدمات التطبيق...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | أسماء مستعارة للكلاسات
    |--------------------------------------------------------------------------
    |
    | مصفوفة الأسماء المستعارة للكلاسات هذه ستُسجل عند بدء التطبيق.
    | ومع ذلك، لا تتردد في تسجيل أي عدد تريده من الأسماء المستعارة لأن
    | الأسماء المستعارة يتم تحميلها "كسولة" لذا لا تضر بالأداء.
    |
    */

    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'Date' => Illuminate\Support\Facades\Date::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Js' => Illuminate\Support\Js::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'RateLimiter' => Illuminate\Support\Facades\RateLimiter::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

        // الأسماء المستعارة للحزم
        'PDF' => Barryvdh\Dompdf\Facade\Pdf::class,
    ],

];
