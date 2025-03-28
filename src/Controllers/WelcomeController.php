<?php

namespace Brucelwayne\Admin\Controllers;

use App\Http\Controllers\Controller;
use Mallria\Core\Facades\Inertia;

class WelcomeController extends Controller
{

    function index()
    {
//        return view('admin::welcome');
        return Inertia::render('Admin/Dashboard/Index');
//        return Inertia::renderVue('Admin/Dashboard');
    }
}