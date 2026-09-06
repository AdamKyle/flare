<?php

namespace App\Admin\LocationGems\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LocationGemsController extends Controller
{
    /**
     * Render the Admin Location Gems application shell.
     *
     * @return View Admin Location Gems application shell view.
     */
    public function index(): View
    {
        return view('admin.location-gems.index');
    }
}
