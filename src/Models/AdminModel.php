<?php

namespace Brucelwayne\Admin\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class AdminModel extends User implements MustVerifyEmail
{
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $table = 'admins';

    protected $guard = 'admin';

    protected $fillable = [
        'name', 'email', 'password'
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'email_verified_at',
        'two_factor_confirmed_at',
        'created_at',
        'updated_at',
    ];

}