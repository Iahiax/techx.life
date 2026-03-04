<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| إنشاء وتكوين التطبيق
|--------------------------------------------------------------------------
|
| يتم هنا إنشاء مثيل التطبيق وتحديد المسارات الأساسية والـ Middleware
| ومعالجة الأخطاء.
|
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // إضافة Middleware إلى مجموعة web
        $middleware->web(append: [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // إضافة Middleware إلى مجموعة api
        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // تسجيل Middleware الفردية بأسماء يمكن استخدامها في المسارات
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // Middleware الخاصة بالمشروع (أنواع المستخدمين)
            'customer' => \App\Http\Middleware\CustomerMiddleware::class,
            'org-user' => \App\Http\Middleware\OrgUserMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // (اختياري) تعريف مجموعات middleware جاهزة
        $middleware->group('customer', [
            'auth',
            'customer',
        ]);

        $middleware->group('org-user', [
            'auth',
            'org-user',
        ]);

        $middleware->group('admin', [
            'auth',
            'admin',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // تخصيص معالجة الأخطاء
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح بالدخول'], 401);
            }
            return redirect()->guest(route('landing'));
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'الصفحة غير موجودة'], 404);
            }
            return response()->view('errors.404', [], 404);
        });
    })
    ->create();
