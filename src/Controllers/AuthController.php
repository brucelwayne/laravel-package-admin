<?php

namespace Brucelwayne\Admin\Controllers;

use App\Http\Controllers\Controller;
use Brucelwayne\Admin\Requests\LoginRequest;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    function login()
    {

        return view('admin::auth.login');
    }

    function attemptLogin(LoginRequest $request)
    {
        $credential = [
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ];
        $remember = $request->validated('remember') === 'on';
        if (Auth::guard('admin')->attempt($credential, $remember)) {
            session()->regenerate();
            return redirect()->intended();
        }
        return redirect()
            ->back()
            ->withInput($request->input())
            ->withErrors([
                'email' => 'Invalid email or password!',
            ]);
    }

    function logout(Request $request)
    {

        Auth::guard('admin')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return to_route('welcome');
    }
}