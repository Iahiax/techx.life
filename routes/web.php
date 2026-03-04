<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\NafathController;
use App\Http\Controllers\Auth\TawtheeqController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\ContractController as CustomerContractController;
use App\Http\Controllers\Customer\AccountController as CustomerAccountController;
use App\Http\Controllers\Customer\ReportController as CustomerReportController;
use App\Http\Controllers\Organization\DashboardController as OrganizationDashboardController;
use App\Http\Controllers\Organization\ContractController as OrganizationContractController;
use App\Http\Controllers\Organization\DeductionController as OrganizationDeductionController;
use App\Http\Controllers\Organization\ReportController as OrganizationReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\ContractController as AdminContractController;
use App\Http\Controllers\Admin\DeductionController as AdminDeductionController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ========== الصفحات العامة ==========
Route::get('/', function () {
    return view('landing.index');
})->name('landing');

// صفحات ثابتة (اختياري)
Route::view('/about', 'landing.about')->name('about');
Route::view('/contact', 'landing.contact')->name('contact');

// ========== مسارات المصادقة عبر نفاذ وتوثيق ==========
Route::prefix('auth')->name('auth.')->group(function () {
    // نفاذ (للأفراد)
    Route::get('/nafath/redirect', [NafathController::class, 'redirect'])->name('nafath.redirect');
    Route::get('/nafath/callback', [NafathController::class, 'callback'])->name('nafath.callback');

    // توثيق (للمنشآت)
    Route::get('/tawtheeq/redirect', [TawtheeqController::class, 'redirect'])->name('tawtheeq.redirect');
    Route::get('/tawtheeq/callback', [TawtheeqController::class, 'callback'])->name('tawtheeq.callback');

    // تسجيل الخروج
    Route::post('/logout', function () {
        Auth::logout();
        return redirect()->route('landing');
    })->name('logout');
});

// ========== مسارات العميل (Customer) ==========
Route::middleware(['auth', 'customer'])->prefix('customer')->name('customer.')->group(function () {
    // لوحة التحكم
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

    // الحسابات البنكية
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [CustomerAccountController::class, 'index'])->name('index');
        Route::get('/create', [CustomerAccountController::class, 'create'])->name('create');
        Route::get('/connect', [CustomerAccountController::class, 'connect'])->name('connect');
        Route::get('/callback', [CustomerAccountController::class, 'callback'])->name('callback');
        Route::put('/{account}', [CustomerAccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [CustomerAccountController::class, 'destroy'])->name('destroy');
        Route::get('/{account}/refresh-balance', [CustomerAccountController::class, 'refreshBalance'])->name('refresh-balance');
    });

    // العقود
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [CustomerContractController::class, 'index'])->name('index');
        Route::get('/{contract}', [CustomerContractController::class, 'show'])->name('show');
        Route::get('/{contract}/review', [CustomerContractController::class, 'review'])->name('review');
        Route::post('/{contract}/approve', [CustomerContractController::class, 'approve'])->name('approve');
        Route::post('/{contract}/reject', [CustomerContractController::class, 'reject'])->name('reject');
        Route::get('/{contract}/print-pdf', [CustomerContractController::class, 'printPdf'])->name('print-pdf');
        Route::get('/{contract}/deductions', [CustomerContractController::class, 'deductions'])->name('deductions');
    });

    // التقارير
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [CustomerReportController::class, 'index'])->name('index');
        Route::get('/contracts', [CustomerReportController::class, 'contractsReport'])->name('contracts');
        Route::get('/deductions', [CustomerReportController::class, 'deductionsReport'])->name('deductions');
        Route::get('/contract/{contract}', [CustomerReportController::class, 'singleContractReport'])->name('single-contract');
    });
});

// ========== مسارات المنشأة (Organization) ==========
Route::middleware(['auth', 'org-user'])->prefix('organization')->name('organization.')->group(function () {
    // لوحة التحكم
    Route::get('/dashboard', [OrganizationDashboardController::class, 'index'])->name('dashboard');

    // العقود
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [OrganizationContractController::class, 'index'])->name('index');
        Route::get('/create', [OrganizationContractController::class, 'create'])->name('create');
        Route::post('/', [OrganizationContractController::class, 'store'])->name('store');
        Route::get('/{contract}', [OrganizationContractController::class, 'show'])->name('show');
        Route::get('/{contract}/edit', [OrganizationContractController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [OrganizationContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [OrganizationContractController::class, 'destroy'])->name('destroy');
        Route::get('/{contract}/print-pdf', [OrganizationContractController::class, 'printPdf'])->name('print-pdf');
        Route::get('/{contract}/deductions', [OrganizationContractController::class, 'deductions'])->name('deductions');
        Route::post('/{contract}/stop-deductions', [OrganizationContractController::class, 'stopDeductions'])->name('stop-deductions');
    });

    // الاستقطاعات
    Route::prefix('deductions')->name('deductions.')->group(function () {
        Route::get('/', [OrganizationDeductionController::class, 'index'])->name('index');
        Route::get('/{deduction}', [OrganizationDeductionController::class, 'show'])->name('show');
    });

    // التقارير
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [OrganizationReportController::class, 'index'])->name('index');
        Route::get('/contracts', [OrganizationReportController::class, 'contractsReport'])->name('contracts');
        Route::get('/deductions', [OrganizationReportController::class, 'deductionsReport'])->name('deductions');
        Route::get('/revenue', [OrganizationReportController::class, 'revenueReport'])->name('revenue');
    });
});

// ========== مسارات المشرف العام (Admin) ==========
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // لوحة التحكم
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // إدارة المستخدمين
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::get('/trashed', [AdminUserController::class, 'trashed'])->name('trashed');
        Route::post('/{user}/restore', [AdminUserController::class, 'restore'])->name('restore');
        Route::post('/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('impersonate');
    });

    // إدارة المنشآت
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', [AdminOrganizationController::class, 'index'])->name('index');
        Route::get('/create', [AdminOrganizationController::class, 'create'])->name('create');
        Route::post('/', [AdminOrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}', [AdminOrganizationController::class, 'show'])->name('show');
        Route::get('/{organization}/edit', [AdminOrganizationController::class, 'edit'])->name('edit');
        Route::put('/{organization}', [AdminOrganizationController::class, 'update'])->name('update');
        Route::delete('/{organization}', [AdminOrganizationController::class, 'destroy'])->name('destroy');
        Route::get('/trashed', [AdminOrganizationController::class, 'trashed'])->name('trashed');
        Route::post('/{organization}/restore', [AdminOrganizationController::class, 'restore'])->name('restore');
        Route::post('/{organization}/toggle-trusted', [AdminOrganizationController::class, 'toggleTrusted'])->name('toggle-trusted');
        Route::post('/{organization}/add-user', [AdminOrganizationController::class, 'addUser'])->name('add-user');
        Route::delete('/{organization}/remove-user/{user}', [AdminOrganizationController::class, 'removeUser'])->name('remove-user');
        Route::post('/{organization}/create-subscription', [AdminOrganizationController::class, 'createSubscription'])->name('create-subscription');
    });

    // إدارة العقود
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [AdminContractController::class, 'index'])->name('index');
        Route::get('/{contract}', [AdminContractController::class, 'show'])->name('show');
        Route::put('/{contract}/status', [AdminContractController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{contract}', [AdminContractController::class, 'destroy'])->name('destroy');
        Route::get('/trashed', [AdminContractController::class, 'trashed'])->name('trashed');
        Route::post('/{contract}/restore', [AdminContractController::class, 'restore'])->name('restore');
    });

    // إدارة الاستقطاعات
    Route::prefix('deductions')->name('deductions.')->group(function () {
        Route::get('/', [AdminDeductionController::class, 'index'])->name('index');
        Route::get('/{deduction}', [AdminDeductionController::class, 'show'])->name('show');
        Route::put('/{deduction}/status', [AdminDeductionController::class, 'updateStatus'])->name('update-status');
        Route::post('/{deduction}/retry', [AdminDeductionController::class, 'retry'])->name('retry');
        Route::post('/contract/{contract}/stop', [AdminDeductionController::class, 'stopContractDeductions'])->name('stop-contract');
        Route::post('/contract/{contract}/resume', [AdminDeductionController::class, 'resumeContractDeductions'])->name('resume-contract');
    });

    // التقارير
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('index');
        Route::get('/users', [AdminReportController::class, 'usersReport'])->name('users');
        Route::get('/organizations', [AdminReportController::class, 'organizationsReport'])->name('organizations');
        Route::get('/contracts', [AdminReportController::class, 'contractsReport'])->name('contracts');
        Route::get('/deductions', [AdminReportController::class, 'deductionsReport'])->name('deductions');
        Route::get('/revenue', [AdminReportController::class, 'revenueReport'])->name('revenue');
        Route::get('/financial-statement', [AdminReportController::class, 'financialStatement'])->name('financial-statement');
    });

    // مسار الخروج من انتحال الشخصية
    Route::get('/leave-impersonation', [AdminUserController::class, 'leaveImpersonation'])->name('leave-impersonation');
});
