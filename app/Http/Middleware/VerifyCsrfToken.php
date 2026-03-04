<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * مسارات URI التي يجب استثناؤها من التحقق من CSRF.
     *
     * @var array
     */
    protected $except = [
        // استثناء webhooks من التحقق لأنها تأتي من خدمات خارجية
        'api/webhook/*',
    ];
}
