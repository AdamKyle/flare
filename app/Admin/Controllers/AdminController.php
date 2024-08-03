<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function home()
    {
        return view('admin.home');
    }

    public function chatLogs()
    {
        return view('admin.chat.logs');
    }
}
