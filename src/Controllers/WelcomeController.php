<?php

namespace Brucelwayne\Admin\Controllers;

use App\Http\Controllers\Controller;
use Mallria\Core\Facades\InertiaAdminFacade;

class WelcomeController extends Controller
{

    function index()
    {
//        return view('admin::welcome');
        return InertiaAdminFacade::render('Admin/Dashboard/Index');
//        return Inertia::renderVue('Admin/Dashboard');
    }
}