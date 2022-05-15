<?php

namespace App\Admin\Controllers;

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

    public function page(InfoPage $infoPage) {
        $pageSections = $infoPage->page_sections;

        usort($pageSections, function ($a, $b) {return $a['display_order'] > $b['display_order']; });

        return view('admin.information.show', [
            'pageTitle' => ucfirst(str_replace('-', ' ', $infoPage->page_name)),
            'sections'  => $pageSections,
            'pageId'    => $infoPage->id,
        ]);
    }
}
