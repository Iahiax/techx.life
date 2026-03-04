<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrgUserMiddleware
{
    /**
     * معالجة الطلب للتحقق من أن المستخدم من نوع موظف منشأة.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('landing');
        }

        if (Auth::user()->type !== 'org_user') {
            abort(403, 'غير مصرح بالدخول لهذه الصفحة.');
        }

        return $next($request);
    }
}
