<?php
namespace Brucelwayne\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email|max:100',
            'password' => 'required|min:8|max:100',
            'remember' => 'sometimes',
        ];
    }
}