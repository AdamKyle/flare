<?php

namespace App\Admin\MapGems\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class MapGemsController extends Controller
{
    /**
     * Render the Admin Map Gems application shell.
     *
     * @return View Admin Map Gems application shell view.
     */
    public function index(): View
    {
        return view('admin.map-gems.index');
    }
}
