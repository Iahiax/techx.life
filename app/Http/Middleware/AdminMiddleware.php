<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * معالجة الطلب للتحقق من أن المستخدم مشرف عام.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('landing');
        }

        if (Auth::user()->type !== 'admin') {
            abort(403, 'غير مصرح بالدخول لهذه الصفحة.');
        }

        return $next($request);
    }
}
