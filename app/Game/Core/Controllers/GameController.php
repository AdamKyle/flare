<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Render the standalone onboarding application while the intro is pending, otherwise the Game application.
     *
     * @param Request $request
     * @return View
     */
    public function game(Request $request): View
    {
        $user = $request->user();

        if ($user->show_intro_page === true) {
            return view('game.onboarding', ['user' => $user]);
        }

        return view('game.game', ['user' => $user]);
    }
}
