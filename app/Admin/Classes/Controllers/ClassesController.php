<?php

namespace App\Admin\Classes\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ClassesController extends Controller
{
    /**
     * Render the Admin Classes application shell.
     *
     * @return View Admin Classes application shell view.
     */
    public function index(): View
    {
        return view('admin.classes.index');
    }
}
