<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    /**
     * معالجة الطلب للتحقق من أن المستخدم من نوع عميل (فرد).
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('landing');
        }

        if (Auth::user()->type !== 'customer') {
            abort(403, 'غير مصرح بالدخول لهذه الصفحة.');
        }

        return $next($request);
    }
}
