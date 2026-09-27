<?php

namespace App\Admin\Skills\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SkillsController extends Controller
{
    /**
     * Render the Admin Skills application shell.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.skills.index');
    }
}
