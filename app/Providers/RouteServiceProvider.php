<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * المسار إلى الصفحة الرئيسية بعد تسجيل الدخول.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * تعريف نماذج ربط المسارات، وفلاتر الأنماط، ومحددات المعدل.
     */
    public function boot(): void
    {
        // تحديد محددات المعدل (Rate Limiters) للطلبات
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // تسجيل المسارات
        $this->routes(function () {
            // مسارات API
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // مسارات الويب
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * (اختياري) يمكنك إضافة أي عمليات إضافية هنا مثل ربط النماذج.
     */
    protected function configureRateLimiting()
    {
        // يمكنك إضافة محددات معدل إضافية هنا
    }
}
