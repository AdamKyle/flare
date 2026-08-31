<?php

namespace App\Admin\Quests\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class QuestsController extends Controller
{
    /**
     * Render the Admin Quests application shell.
     *
     * @return View Admin Quests application shell view.
     */
    public function index(): View
    {
        return view('admin.quests.index');
    }
}
