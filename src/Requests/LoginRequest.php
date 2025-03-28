<?php

namespace Brucelwayne\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email|max:100',
            'password' => 'required|min:6|max:100',
            'remember' => 'sometimes',
//            'g-recaptcha-response' => 'recaptcha',
//            'captcha' => 'required|captcha',
        ];
    }
}