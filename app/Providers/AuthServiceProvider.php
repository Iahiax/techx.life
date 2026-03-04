<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * سياسات النموذج للتطبيق.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        // يمكنك إضافة السياسات هنا مثل:
        // \App\Models\Contract::class => \App\Policies\ContractPolicy::class,
    ];

    /**
     * تسجيل أي خدمات مصادقة / تفويض.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // تعريف بوابات (Gates) مخصصة هنا إذا لزم الأمر
        // Gate::define('view-contracts', function ($user, $contract) {
        //     return $user->id === $contract->customer_id || $user->type === 'admin';
        // });

        // مثال: Gate للمشرفين فقط
        Gate::define('access-admin', function ($user) {
            return $user->type === 'admin';
        });

        // Gate للتحقق من مالك العقد (العميل)
        Gate::define('owns-contract', function ($user, $contract) {
            return $user->id === $contract->customer_id;
        });

        // Gate للتحقق من أن المستخدم ينتمي إلى المنشأة صاحبة العقد
        Gate::define('belongs-to-contract-org', function ($user, $contract) {
            return $user->orgUsers()->where('org_id', $contract->org_id)->exists();
        });
    }
}
