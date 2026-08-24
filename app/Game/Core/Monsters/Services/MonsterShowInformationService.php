<?php

namespace App\Game\Core\Monsters\Services;

use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Game\Core\Items\Services\ItemShowInformationService;

class MonsterShowInformationService
{
    public function __construct(private readonly ItemShowInformationService $itemShowInformationService) {}

    /**
     * Build the Game monster show details for the given Monster.
     */
    public function details(Monster $monster): array
    {
        $quest = null;
        $questItem = null;

        if (! is_null($monster->questItem)) {
            $quest = Quest::where('item_id', $monster->questItem->id)->first();
            $questItem = $this->itemShowInformationService->details($monster->questItem);
        }

        return [
            'monster' => $monster,
            'quest' => $quest,
            'questItem' => $questItem,
        ];
    }
}
