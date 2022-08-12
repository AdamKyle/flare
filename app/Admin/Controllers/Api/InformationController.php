<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\InformationManagementRequest;
use App\Admin\Services\InfoPageService;
use App\Flare\Models\InfoPage;
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

    public function getPage(Request $request) {
        $page = InfoPage::find($request->page_id);

        return response()->json([
            'page_name' => $page->page_name,
            'page_sections' => $this->infoPageService->formatForEditor($page->page_sections),
        ]);
    }

    public function storePage(InformationManagementRequest $request) {
        $page = $this->infoPageService->createPage($request->all());

        return response()->json([
           'pageId' => $page->id
        ]);
    }

    public function updatePage(InformationManagementRequest $request) {

        $page = InfoPage::find($request->page_id);

        if (is_null($page)) {
            return response()->json([
                'message' => 'Page does not exist.'
            ], 422);
        }

        $this->infoPageService->updatePage($page, $request->all());

        return response()->json([
            'pageId' => $page->id,
        ]);
    }

    public function deleteSection(Request $request, InfoPage $infoPage) {
        $page = $this->infoPageService->deleteSectionFromPage($infoPage, $request->order);

        return response()->json([
            'sections' => $page->sections,
        ]);
    }

    public function deletePage(Request $request) {

        $page = InfoPage::find($request->page_id);

        if (is_null($page)) {
            return response()->json([
                'message' => 'Page does not exist.'
            ], 422);
        }

        $this->infoPageService->deleteStoredImages($page->page_sections, $page->page_name);

        $page->delete();

        return response()->json([
            'message' => 'Page deleted.'
        ]);
    }
}
