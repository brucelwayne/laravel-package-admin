<?php

namespace Brucelwayne\Admin\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class AdminModel extends User implements MustVerifyEmail,HasMedia
{
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HashableId;
    use InteractsWithMedia;

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

    //region hash id
    protected $hashKey = self::class;
    protected $appends = [
        'hash'
    ];
    public function getRouteKeyName(): string
    {
        return 'hash';
    }
    //endregion

    public function registerMediaConversions($media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10);
    }
}