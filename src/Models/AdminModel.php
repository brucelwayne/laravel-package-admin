<?php

namespace Brucelwayne\Admin\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class AdminModel extends User implements MustVerifyEmail, HasMedia
{
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HashableId;
    use InteractsWithMedia;

    protected $table = 'blw_admins';

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
    protected $hashKey = 'blw_admins';
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
        generate_thumbnail($this, $media);
//        $this->addMediaConversion('thumb')
//            ->width(368)
//            ->height(232)
//            ->sharpen(10);
    }
}