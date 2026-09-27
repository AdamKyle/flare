<?php

namespace App\Admin\Kingdoms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class UnitsController extends Controller
{
    /**
     * Render the Admin Kingdom Units application shell.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.kingdoms.units.index');
    }
}
