<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * أسماء ملفات تعريف الارتباط التي لا يجب تشفيرها.
     *
     * @var array
     */
    protected $except = [
        // يمكنك إضافة أسماء الكوكيز التي لا تريد تشفيرها هنا
        // مثلاً إذا كنت تستخدم حزمة معينة تحتاج إلى قراءة الكوكيز بدون تشفير
    ];
}
