<?php

namespace App\Admin\Monsters\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class MonstersController extends Controller
{
    /**
     * Render the Admin Monsters application shell.
     *
     * @return View Admin Monsters application shell view.
     */
    public function index(): View
    {
        return view('admin.monsters.index');
    }
}
