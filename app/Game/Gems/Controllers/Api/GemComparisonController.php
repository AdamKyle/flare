<?php

namespace App\Game\Gems\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gems\Requests\CompareGemsRequest;
use App\Game\Gems\Services\GemComparison;
use App\Http\Controllers\Controller;

class GemComparisonController extends Controller
{

    /**
     * @var GemComparison $gemComparison
     */
    private GemComparison $gemComparison;

    /**
     * @param GemComparison $gemComparison
     */
    public function __construct(GemComparison $gemComparison) {
        $this->gemComparison = $gemComparison;
    }

    public function compareGems(Character $character, CompareGemsRequest $compareGemsRequest) {
        $result = $this->gemComparison->compareGemForItem($character, $compareGemsRequest->slot_id, $compareGemsRequest->gem_slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
