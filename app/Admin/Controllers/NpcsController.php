<?php

namespace App\Admin\Controllers;


use App\Flare\Values\NpcTypes;
use App\Http\Controllers\Controller;
use App\Flare\Models\Npc;

class NpcsController extends Controller {

    public function index() {
        return view('admin.npcs.index');
    }

    public function show(Npc $npc) {

        $npc->game_map_name = $npc->gameMap->name;
        $npc->type          = (new NpcTypes($npc->type))->getNamedValue();

        return view('admin.npcs.show', [
            'npc' => $npc,
        ]);
    }

    public function create() {
        return view('admin.npcs.manage', [
            'npc'     => null,
            'editing' => false,
        ]);
    }

    public function edit(Npc $npc) {
        return view('admin.npcs.manage', [
            'npc'     => $npc,
            'editing' => true,
        ]);
    }
}
