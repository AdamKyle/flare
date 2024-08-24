<?php

namespace App\Game\Core\Controllers\Api;

use App\Admin\Services\SuggestionAndBugsService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Requests\SuggestionsAndBugsRequest;
use Illuminate\Http\JsonResponse;

class SuggestionsAndBugsController extends Controller {

    /**
     * @param SuggestionAndBugsService $suggestionAndBugsService
     */
    public function __construct(private readonly SuggestionAndBugsService $suggestionAndBugsService){
    }


    /**
     * @param SuggestionsAndBugsRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function submitEntry(SuggestionsAndBugsRequest $request, Character $character): JsonResponse {
        $result = $this->suggestionAndBugsService->createEntry($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
