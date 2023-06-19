<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\ItemSkillService;
use App\Http\Controllers\Controller;

class ItemSkillController extends Controller {

    use ResponseBuilder, FetchEquipped;

    /**
     * @var ItemSkillService
     */
    private ItemSkillService $itemSkillService;

    /**
     * @param ItemSkillService $itemSkillService
     */
    public function __construct(ItemSkillService $itemSkillService) {
        $this->itemSkillService = $itemSkillService;
    }

    public function trainSkill(Character $character, int $itemId, int $itemSkillProgressionId) {

        $result = $this->itemSkillService->trainSkill($character, $itemId, $itemSkillProgressionId);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    public function stopTrainingSkill(Character $character, int $itemId, int $itemSkillProgressionId) {
        $result = $this->itemSkillService->stopTrainingSkill($character, $itemId, $itemSkillProgressionId);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}