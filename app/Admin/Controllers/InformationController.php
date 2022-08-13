<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\InfoImport;
use App\Http\Controllers\Controller;
use App\Flare\Models\InfoPage;

class InformationController extends Controller {

    public function index() {
        return view('admin.information.list');
    }

    public function createPage() {
        return view('admin.information.manage', [
            'infoPageId' => 0,
        ]);
    }

    public function managePage(InfoPage $infoPage) {
        return view('admin.information.manage', [
            'infoPageId' => $infoPage->id,
        ]);
    }

    public function export() {
        return response()->attachment(InfoPage::all(), 'inforamtion');
    }

    public function import(InfoImport $request) {

        $data = json_decode(trim($request->file('info_import')->get()), true);

        foreach ($data as $key => $modelEntry) {
            $displayOrder = 1;

            foreach ($modelEntry['page_sections'] as $index => $section) {
                if (!isset($section['display_order'])) {
                    continue;
                }

                if ($index === 0) {
                    $displayOrder = $section['display_order'];

                    unset($section['display_order']);

                    $section['order'] = $displayOrder;

                    $modelEntry['page_sections'][$index] = $section;

                    $displayOrder++;

                    continue;
                }

                if ($index !== 0) {
                    unset($section['display_order']);

                    $section['order'] = $displayOrder;

                    $modelEntry['page_sections'][$index] = $section;

                    $displayOrder++;
                }
            }

            InfoPage::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }

        return response()->redirectToRoute('admin.info-management')->with('success', 'Info has been imported. Do not forget to sync up the backup images.');
    }

    public function exportInfo() {
        return response()->view('admin.information.export');
    }

    public function importInfo() {
        return response()->view('admin.information.import');
    }

    public function page(InfoPage $infoPage) {
        $pageSections = $infoPage->page_sections;

        array_multisort(array_column($pageSections, 'order'), SORT_ASC, $pageSections);

        return view('admin.information.show', [
            'pageTitle' => ucfirst(str_replace('-', ' ', $infoPage->page_name)),
            'sections'  => $pageSections,
            'pageId'    => $infoPage->id,
        ]);
    }
}
