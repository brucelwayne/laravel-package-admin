<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Mallria\Core\Enums\PostStatus;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Shop\Models\TransProductModel;

class ProductsController extends BaseAdminController
{
    function index(Request $request)
    {
        $products = TransProductModel::where('status', PostStatus::ReadyForApproval)
            ->cursorPaginate(20);

        return InertiaAdminFacade::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }
}