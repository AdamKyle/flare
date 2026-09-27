<?php

namespace App\Admin\PassiveSkills\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PassiveSkillsController extends Controller
{
    /**
     * Render the Admin Passive Skills application shell.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.passive-skills.index');
    }
}
