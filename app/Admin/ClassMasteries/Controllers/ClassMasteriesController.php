<?php

namespace App\Admin\ClassMasteries\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ClassMasteriesController extends Controller
{
    /**
     * Render the Admin Class Masteries application shell.
     *
     * @return View Admin Class Masteries application shell view.
     */
    public function index(): View
    {
        return view('admin.class-masteries.index');
    }
}
