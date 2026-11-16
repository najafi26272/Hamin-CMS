<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * حالت توسعه سایت (تنظیمات سایت ← حالت توسعه)
 * - مدیر اصلی و نویسندگان سایت و پنل را عادی می‌بینند.
 * - کاربر واردشده دیگر از حساب خارج می‌شود.
 * - بقیه به جای صفحه‌های سایت، صفحه «در حال توسعه» را می‌بینند (کد 503).
 * - صفحه ورود، درخواست‌های Livewire و پنل (که خودش ورود می‌خواهد) باز می‌مانند.
 */
class SiteMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::maintenance()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->canBypassMaintenance()) {
            return $next($request);
        }

        if ($user) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (! $request->is('livewire-*')) {
                return redirect()->route('login')->with('error', 'سایت در حال توسعه است؛ در این مدت فقط مدیران و نویسندگان می‌توانند وارد شوند.');
            }
        }

        if ($request->routeIs('login') || $request->is('livewire-*', 'panel', 'panel/*')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'سایت در حال توسعه است.'], 503);
        }

        return response()->view('site.maintenance', [], 503)->header('Retry-After', '3600');
    }
}
