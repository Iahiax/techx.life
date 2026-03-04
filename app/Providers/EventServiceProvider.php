<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * تعيين ما إذا كان سيتم اكتشاف الأحداث تلقائيًا.
     *
     * @var bool
     */
    protected $listen = [
        // حدث تسجيل مستخدم جديد
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // أحداث مخصصة للمشروع
        // يمكن إضافة الأحداث والمستمعين هنا حسب الحاجة
        // \App\Events\ContractCreated::class => [
        //     \App\Listeners\SendContractNotification::class,
        // ],
    ];

    /**
     * مستمعو الأحداث للاشتراك في أحداث متعددة.
     *
     * @var array
     */
    protected $subscribe = [
        // \App\Listeners\UserEventSubscriber::class,
    ];

    /**
     * تسجيل أي خدمات حدث.
     */
    public function boot(): void
    {
        parent::boot();

        // يمكنك تسجيل أحداث مخصصة هنا باستخدام Closure
        // Event::listen(function (\App\Events\SomeEvent $event) {
        //     //
        // });
    }

    /**
     * تحديد ما إذا كان يجب اكتشاف الأحداث تلقائيًا.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
