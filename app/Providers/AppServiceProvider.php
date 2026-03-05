<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // يمكنك ربط الواجهات بالخدمات هنا إذا لزم الأمر
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تعيين طول افتراضي للـ string في MySQL لتجنب أخطاء المفاتيح
        Schema::defaultStringLength(191);

        // إذا كان التطبيق في بيئة الإنتاج، نقوم بما يلي:
        if ($this->app->environment('production')) {
            // فرض استخدام HTTPS في جميع الروابط المولدة
            URL::forceScheme('https');

            // إخفاء عرض الأخطاء والتحذيرات (تظهر فقط في السجلات)
            error_reporting(0);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }
    }
}
