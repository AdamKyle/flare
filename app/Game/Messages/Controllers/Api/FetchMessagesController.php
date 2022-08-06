<?php

namespace App\Game\Messages\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Game\Messages\Services\FetchMessages;
use App\Http\Controllers\Controller;

class FetchMessagesController extends Controller {

    /**
     * @var FetchMessages $fetchMessages
     */
    private FetchMessages $fetchMessages;

    /**
     * @param FetchMessages $fetchMessages
     */
    public function __construct(FetchMessages $fetchMessages) {
        $this->fetchMesages = $fetchMessages;
    }

    /**
     * @return JsonResponse
     */
    public function fetchChatMessages(): JsonResponse {
        return response()->json($this->fetchMesages->fetchMessages());
    }
}
