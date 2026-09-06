<?php

namespace App\Admin\Races\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class RacesController extends Controller
{
    /**
     * Render the Admin Races application shell.
     *
     * @return View Admin Races application shell view.
     */
    public function index(): View
    {
        return view('admin.races.index');
    }
}
