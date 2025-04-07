<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Mallria\Business\Models\BusinessModel;
use Mallria\Core\Facades\InertiaAdminFacade;

class SellerController extends BaseAdminController
{
    function index(Request $request)
    {
        $tenants = BusinessModel::where('verified', false)->cursorPaginate(20);

        return InertiaAdminFacade::render('Admin/Tenant/Index', [
            'tenants' => $tenants,
        ]);
    }
}