<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DeductionController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// المسارات العامة (بدون مصادقة) - تستخدم للـ Webhooks من الخدمات الخارجية
Route::post('/webhook/lean', [WebhookController::class, 'handle'])
    ->name('api.webhook.lean');
Route::post('/webhook/sadad', [WebhookController::class, 'handle'])
    ->name('api.webhook.sadad');
// يمكن أيضاً استخدام نقطة دخول موحدة مع تحديد المزود
Route::post('/webhook/{provider}', [WebhookController::class, 'handle'])
    ->name('api.webhook.provider');

// المسارات المحمية بالمصادقة ( Sanctum )
Route::middleware('auth:sanctum')->group(function () {
    
    // مسارات العقود
    Route::apiResource('contracts', ContractController::class)
        ->except(['create', 'edit']); // نستثني create,edit لأنها غير ضرورية في API
    
    // مسارات الاستقطاعات (للاستعلام فقط)
    Route::prefix('deductions')->name('deductions.')->group(function () {
        Route::get('/', [DeductionController::class, 'index'])->name('index');
        Route::get('/stats', [DeductionController::class, 'stats'])->name('stats');
        Route::get('/{id}', [DeductionController::class, 'show'])->name('show');
    });

    // (اختياري) مسار للاستعلام عن حالة المستخدم/المنشأة
    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user()->load('orgUsers.organization');
    })->name('api.user');

    // مسارات إضافية للعقود: إذا أردنا تحديث حالة العقد عبر API (اختياري)
    Route::post('/contracts/{contract}/status', [ContractController::class, 'updateStatus'])
        ->name('api.contracts.status');
});
