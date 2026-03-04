<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * معالجة الطلب الوارد.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // توجيه المستخدم حسب نوعه
                $user = Auth::user();
                if ($user->type === 'customer') {
                    return redirect()->route('customer.dashboard');
                } elseif ($user->type === 'org_user') {
                    return redirect()->route('organization.dashboard');
                } elseif ($user->type === 'admin') {
                    return redirect()->route('admin.dashboard');
                }
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
