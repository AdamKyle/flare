<?php

namespace App\Game\Maps\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gems\Progression\Requests\GemScrollPaginationRequest;
use App\Game\Gems\Progression\Services\GemProgressionCollectionReadService;
use App\Game\Gems\Progression\Services\GemProgressionReadService;
use App\Game\Gems\Progression\Services\GemScrollReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GemWorldProgressController extends Controller
{
    /**
     * @param GemProgressionReadService $gemProgressionReadService
     * @param GemScrollReadService $gemScrollReadService
     * @param GemProgressionCollectionReadService $gemProgressionCollectionReadService
     */
    public function __construct(
        private readonly GemProgressionReadService $gemProgressionReadService,
        private readonly GemScrollReadService $gemScrollReadService,
        private readonly GemProgressionCollectionReadService $gemProgressionCollectionReadService,
    ) {}

    /**
     * Return the Character's current Gem progression status.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function current(Character $character): JsonResponse
    {
        return response()->json($this->gemProgressionReadService->currentStatus($character), 200);
    }

    /**
     * Paginate the Character's active Gem Scrolls for their current generated Gem World profile.
     *
     * @param GemScrollPaginationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function currentProfileActiveScrolls(GemScrollPaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json($this->gemScrollReadService->currentProfileActiveScrolls($character, $request->page, $request->per_page), 200);
    }

    /**
     * Paginate every unexpired active Gem Scroll the Character owns across every Map/Location profile.
     *
     * @param GemScrollPaginationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function allActiveScrolls(GemScrollPaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json($this->gemScrollReadService->allActiveScrolls($character, $request->page, $request->per_page), 200);
    }

    /**
     * Paginate every Gem World profile the Character has personal progression participation in.
     *
     * @param GemScrollPaginationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function allProfileParticipation(GemScrollPaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json($this->gemProgressionCollectionReadService->allProfileParticipation($character, $request->page, $request->per_page), 200);
    }
}
