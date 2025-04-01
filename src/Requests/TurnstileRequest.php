<?php

namespace Brucelwayne\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;

class TurnstileRequest extends FormRequest
{
    public function rules()
    {
        return [
            'cf-turnstile-response' => 'required'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $turnstileResponse = $this->input('cf-turnstile-response');

            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => env('TURNSTILE_SECRET_KEY'),
                'response' => $turnstileResponse,
                'remoteip' => $this->ip(),
            ]);

            if (!$response->json('success')) {
                $validator->errors()->add('cf-turnstile-response', '验证码验证失败，请重试');
            }
        });
    }
}
