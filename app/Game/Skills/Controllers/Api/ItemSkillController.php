<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\ItemSkillService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemSkillController extends Controller
{
    use FetchEquipped, ResponseBuilder;

    public function __construct(
        private ItemSkillService $itemSkillService,
        private readonly AutomationRestrictionService $automationRestrictionService
    ) {}

    public function trainSkill(Character $character, int $itemId, int $itemSkillProgressionId): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->itemSkillService->trainSkill($character, $itemId, $itemSkillProgressionId);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    public function stopTrainingSkill(Character $character, int $itemId, int $itemSkillProgressionId): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->itemSkillService->stopTrainingSkill($character, $itemId, $itemSkillProgressionId);
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::PLAYER_SKILLS);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
