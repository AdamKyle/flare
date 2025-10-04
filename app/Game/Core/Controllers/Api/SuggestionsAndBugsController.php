<?php

namespace App\Game\Core\Controllers\Api;

use App\Admin\Services\SuggestionAndBugsService;
use App\Flare\Models\Character;
use App\Game\Core\Requests\SuggestionsAndBugsRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SuggestionsAndBugsController extends Controller
{
    public function __construct(private readonly SuggestionAndBugsService $suggestionAndBugsService) {}

    public function submitEntry(SuggestionsAndBugsRequest $request, Character $character): JsonResponse
    {
        $result = $this->suggestionAndBugsService->createEntry($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
