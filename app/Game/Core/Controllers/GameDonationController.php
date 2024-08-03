<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;

class GameDonationController extends Controller
{
    public function donationSection()
    {
        return view('game.donations.donation-page');
    }
}
