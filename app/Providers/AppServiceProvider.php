<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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

        // إذا كان التطبيق في بيئة الإنتاج، فرض استخدام HTTPS في الروابط
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
