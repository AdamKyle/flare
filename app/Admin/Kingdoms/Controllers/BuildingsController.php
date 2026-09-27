<?php

namespace App\Admin\Kingdoms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class BuildingsController extends Controller
{
    /**
     * Render the Admin Kingdom Buildings application shell.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.kingdoms.buildings.index');
    }
}
