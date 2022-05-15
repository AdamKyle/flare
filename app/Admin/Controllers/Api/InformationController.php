<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\InfoPageService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InformationController extends Controller {

    /**
     * @var InfoPageService
     */
    private InfoPageService $infoPageService;

    public function __construct(InfoPageService $infoPageService) {
        $this->infoPageService = $infoPageService;
    }

    public function storePage(Request $request) {
        $this->infoPageService->createPage($request->all());

        return response()->json([
            'message' => $request['page_name'] . ' has been saved.',
        ]);
    }
}
