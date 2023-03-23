<?php

namespace App\Game\Core\Gems\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Gems\Requests\CompareGemsRequest;
use App\Game\Core\Gems\Services\GemComparison;

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
