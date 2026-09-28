<?php

namespace App\Admin\GemAbilities\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class GemAbilitiesController extends Controller
{
    /**
     * Render the Admin Gem Abilities application shell.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.gem-abilities.index');
    }
}
