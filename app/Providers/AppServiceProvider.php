<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * تسجيل أي خدمات تطبيق.
     */
    public function register(): void
    {
        // يمكنك ربط الواجهات بالخدمات هنا
        // $this->app->bind(SomeInterface::class, SomeImplementation::class);
    }

    /**
     * تشغيل أي خدمات بعد تسجيل جميع مقدمي الخدمات.
     */
    public function boot(): void
    {
        // تعيين طول افتراضي للـ string في MySQL لتجنب أخطاء المفاتيح
        Schema::defaultStringLength(191);

        // إذا كان التطبيق في بيئة الإنتاج، فرض استخدام HTTPS في الروابط
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // (اختياري) تعيين اللغة الافتراضية للتطبيق إلى العربية
        // config(['app.locale' => 'ar']);
        // config(['app.fallback_locale' => 'en']);
    }
}
