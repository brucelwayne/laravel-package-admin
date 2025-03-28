<?php

namespace Brucelwayne\Admin\Controllers;

namespace Brucelwayne\Admin\Controllers;

use App\Http\Controllers\Controller;
use Brucelwayne\Admin\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;

class AuthController extends Controller
{
    function login()
    {
        return inertia('Admin/Auth/Login');
    }

    function attemptLogin(LoginRequest $request)
    {
        $credential = [
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ];
        $remember = $request->validated('remember') === true;

        if (Auth::guard('admin')->attempt($credential, $remember)) {
            session()->regenerate();
            return new SuccessJsonResponse();
        }

        return new ErrorJsonResponse(__('Invalid email or password!'), [], 402);
    }

    function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return new SuccessJsonResponse();
    }
}
