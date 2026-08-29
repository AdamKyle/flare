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
     *
     * @return View Admin NPCs application shell view.
     */
    public function index(): View
    {
        return view('admin.npcs.index');
    }

    /**
     * Render the legacy informational NPC page for the given NPC.
     *
     * @param  Npc  $npc  NPC to describe.
     * @return View Legacy informational NPC page view.
     */
    public function show(Npc $npc): View
    {
        $npc->game_map_name = $npc->gameMap->name;
        $npc->type_name = NpcType::from($npc->type)->getNamedValue();

        return view('admin.npcs.show', ['npc' => $npc]);
    }

    /**
     * Redirect legacy NPC edit links into the modern Admin NPCs application.
     *
     * @return RedirectResponse Redirect to the Admin NPCs application.
     */
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.npcs.index');
    }
}
