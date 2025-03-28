<?php

namespace Brucelwayne\Admin\Models;

use App\Models\User;

class AdminUserModel extends User
{
    protected $guard = 'admin';

}