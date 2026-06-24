<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function exploration(): View
    {
        return view('admin.monitoring.exploration');
    }

    public function factionLoyalty(): View
    {
        return view('admin.monitoring.faction-loyalty');
    }

    public function delve(): View
    {
        return view('admin.monitoring.delve');
    }

    public function logs(): View
    {
        return view('admin.monitoring.logs');
    }
}
