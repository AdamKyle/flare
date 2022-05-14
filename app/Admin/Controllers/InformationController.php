<?php

namespace App\Admin\Controllers;

use App\Admin\Services\InfoPageService;
use Illuminate\Http\Request;
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

    }
}
