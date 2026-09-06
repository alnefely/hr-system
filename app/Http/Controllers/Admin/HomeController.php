<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function home(Request $request)
    {
        return view('admin.home.index');
    }

    // error_403
    public function error_403(Request $request)
    {
        return view('admin.errors.403');
    }
}
