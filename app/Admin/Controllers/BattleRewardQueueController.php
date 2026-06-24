<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BattleRewardQueueController extends Controller
{
    public function index(): View
    {
        return view('admin.battle-reward-queue.index');
    }
}
