<?php

namespace Brucelwayne\Admin\Controllers;

use App\Http\Controllers\Controller;
use Brucelwayne\Admin\Requests\LoginRequest;
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
        return redirect()->back()->withErrors([
            'email' => 'Invalid email or password!',
        ]);
    }
}