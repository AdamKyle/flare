<?php

namespace App\Admin\GameMaps\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class GameMapsController extends Controller
{
    /**
     * Render the Admin Game Maps application shell.
     */
    public function index(): View
    {
        return view('admin.game-maps.index');
    }
}
