<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\ItemSkillService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemSkillController extends Controller
{
    use FetchEquipped, ResponseBuilder;

    public function __construct(private ItemSkillService $itemSkillService) {}

    public function trainSkill(Character $character, int $itemId, int $itemSkillProgressionId): JsonResponse
    {

        $result = $this->itemSkillService->trainSkill($character, $itemId, $itemSkillProgressionId);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    public function stopTrainingSkill(Character $character, int $itemId, int $itemSkillProgressionId): JsonResponse
    {
        $result = $this->itemSkillService->stopTrainingSkill($character, $itemId, $itemSkillProgressionId);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
