<?php

namespace App\Http\Controllers;

class MarketingPagesController extends Controller
{
    public function features()
    {
        return view('marketing.features');
    }

    public function whosPlaying() {
        return view('marketing.whos-playing');
    }
}
