<?php

namespace App\Admin\Npcs\Controllers;

use App\Flare\Models\Npc;
use App\Game\Npcs\Values\NpcType;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NpcsController extends Controller
{
    /**
     * Render the Admin NPCs application shell.
     */
    public function index(): View
    {
        return view('admin.npcs.index');
    }

    /**
     * Render the legacy informational NPC page for the given NPC.
     */
    public function show(Npc $npc): View
    {
        $npc->game_map_name = $npc->gameMap->name;
        $npc->type_name = NpcType::from($npc->type)->getNamedValue();

        return view('admin.npcs.show', ['npc' => $npc]);
    }

    /**
     * Redirect legacy NPC edit links into the modern Admin NPCs application.
     */
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.npcs.index');
    }
}
