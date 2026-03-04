<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * مسارات URI التي يجب أن تكون قابلة للوصول أثناء وضع الصيانة.
     *
     * @var array
     */
    protected $except = [
        // يمكن إضافة مسارات لا تتأثر بوضع الصيانة مثل webhooks
        // 'api/webhook/*',
    ];
}
