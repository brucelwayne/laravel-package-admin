<?php

namespace Brucelwayne\Admin\Controllers;

use Brucelwayne\Admin\Requests\TurnstileLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Mallria\Core\Facades\Inertia;
use Mallria\Main\Controllers\BaseAuthController;

class AuthController extends BaseAuthController
{
    function login()
    {
        $from = $_SERVER['HTTP_REFERER'] ?? route('business.dashboard');
        // 获取应用的完整 URL
        $appUrl = config('app.url');
        // 判断来源 URL 是否以应用完整域名开头
        if (!str_starts_with($from, $appUrl)) {
            // 如果来源不是应用域名，则重定向到用户的仪表板
            $from = route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login', [
            'from' => $from,
            'nonce' => Str::random(32),
        ]);
    }

    function sendEmailOtp(TurnstileLoginRequest $request)
    {
        return $this->sendOtp($request);
    }

    function verityEmailOtp(Request $request)
    {
        return $this->verifyAndLogin($request, 'admin'); // 普通用户使用 'web' guard
    }

    function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        return Inertia::location(route('admin.login'));
    }
}
