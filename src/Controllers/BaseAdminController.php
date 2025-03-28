<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Support\Facades\Auth;
use Mallria\Core\Http\Controllers\BaseController;

class BaseAdminController extends BaseController
{
    public function __construct()
    {
        // 指定默认使用 'admin' guard
        Auth::shouldUse('admin');

        // 确保用户必须通过 admin guard 认证
        // 已经在web.php里增加了
//        $this->middleware('auth:admin');

    }
}