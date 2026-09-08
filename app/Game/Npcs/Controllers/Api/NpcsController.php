<?php

namespace App\Game\Npcs\Controllers\Api;

use App\Flare\Models\Npc;
use App\Game\Npcs\Transformers\NpcDetailTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NpcsController extends Controller
{
    public function __construct(
        private readonly NpcDetailTransformer $npcDetailTransformer,
    ) {}

    /**
     * Return the Player-safe factual detail representation for the given NPC.
     */
    public function show(Npc $npc): JsonResponse
    {
        return response()->json($this->npcDetailTransformer->transform($npc), 200);
    }
}
