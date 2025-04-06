<?php

namespace Brucelwayne\Admin\Requests;

use Brucelwayne\Admin\Models\AdminUserModel;
use Illuminate\Foundation\Http\FormRequest;

class TurnstileLoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email|max:100',
            'cf-turnstile-response' => 'required', // Ensure Turnstile response field is present
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // 1. First, verify Cloudflare Turnstile
            $turnstileResponse = $this->input('cf-turnstile-response');

            // 检查 $turnstileResponse 是否为空
            if (empty($turnstileResponse)) {
                $validator->errors()->add('cf-turnstile-response', 'Captcha response is missing. Please try again.');
                return; // 直接返回，停止后续验证
            }


            if (!turnstile_validate($turnstileResponse)) {
                $validator->errors()->add('cf-turnstile-response', 'Captcha verification failed. Please try again.');
                return; // Stop further validation if Turnstile verification fails
            }

            // 2. Then, check if the user is an admin
            $user = AdminUserModel::where('email', $this->input('email'))->first();

            if (!$user || !$this->isAdmin($user)) {
                $validator->errors()->add('email', 'Invalid request.');
            }
        });
    }

    private function isAdmin(AdminUserModel $user)
    {
        return $user->is_admin; // Ensure only admin users pass
    }
}
