<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Quest;

class QuestsController extends Controller {

    public function index() {
        return view('admin.quests.index');
    }

    public function show(Quest $quest) {
        return view('admin.quests.show', [
            'quest' => $quest,
        ]);
    }

    public function create() {
        return view('admin.quests.manage', [
            'quest'   => null,
            'editing' => false,
        ]);
    }

    public function edit(Quest $quest) {
        return view('admin.quests.manage', [
            'quest'   => $quest,
            'editing' => true,
        ]);
    }
}
