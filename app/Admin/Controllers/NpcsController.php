<?php

namespace App\Admin\Controllers;


use App\Http\Controllers\Controller;
use App\Flare\Models\Npc;

class NpcsController extends Controller {

    public function index() {
        return view('admin.npcs.index');
    }

    public function show(Npc $npc) {
        return view('admin.npcs.show', [
            'npc' => $npc,
        ]);
    }

    public function create() {
        return view('admin.monsters.manage', [
            'npc' => null,
            'editing' => false,
        ]);
    }

    public function edit(Npc $npc) {
        return view('admin.monsters.manage', [
            'monster' => $npc,
            'editing' => true,
        ]);
    }
}
