<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\InformationManagementAddSectionRequest;
use App\Admin\Requests\InformationManagementRequest;
use App\Admin\Requests\InformationManagementUpdateRequest;
use App\Admin\Services\InfoPageService;
use App\Flare\Models\InfoPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InformationController extends Controller
{
    private InfoPageService $infoPageService;

    public function __construct(InfoPageService $infoPageService)
    {
        $this->infoPageService = $infoPageService;
    }

    public function getPage(Request $request)
    {
        $page = InfoPage::find($request->page_id);

        return response()->json([
            'page_name' => $page->page_name,
            'page_sections' => $this->infoPageService->formatForEditor($page->page_sections),
        ]);
    }

    public function storePage(InformationManagementRequest $request)
    {
        $page = $this->infoPageService->createPage($request->all());

        return response()->json([
            'pageId' => $page->id,
            'page_sections' => $this->infoPageService->formatForEditor($page->page_sections),
        ]);
    }

    public function updatePage(InformationManagementUpdateRequest $request)
    {

        $page = InfoPage::find($request->page_id);

        if (is_null($page)) {
            return response()->json([
                'message' => 'Page does not exist.',
            ], 422);
        }

        $this->infoPageService->updatePage($page, $request->all());

        $page = $page->refresh();

        return response()->json([
            'pageId' => $page->id,
            'page_sections' => $this->infoPageService->formatForEditor($page->page_sections),
        ]);
    }

    public function addSection(InformationManagementAddSectionRequest $request)
    {
        $page = InfoPage::find($request->page_id);

        if (is_null($page)) {
            return response()->json([
                'message' => 'Page does not exist.',
            ], 422);
        }

        $this->infoPageService->addSections($page, $request->section_to_insert);

        $page = $page->refresh();

        return response()->json([
            'page_name' => $page->page_name,
            'page_sections' => $this->infoPageService->formatForEditor($page->page_sections),
        ]);
    }

    public function deleteSection(Request $request, InfoPage $infoPage)
    {
        $page = $this->infoPageService->deleteSectionFromPage($infoPage, $request->order);

        $page = $page->refresh();

        return response()->json([
            'page_sections' => $this->infoPageService->formatForEditor($page->page_sections),
        ]);
    }

    public function deletePage(Request $request)
    {

        $page = InfoPage::find($request->page_id);

        if (is_null($page)) {
            return response()->json([
                'message' => 'Page does not exist.',
            ], 422);
        }

        $this->infoPageService->deleteStoredImages($page->page_sections, $page->page_name);

        $page->delete();

        return response()->json([
            'message' => 'Page deleted.',
        ]);
    }
}
