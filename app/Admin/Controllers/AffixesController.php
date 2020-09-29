<?php

namespace App\Admin\Controllers;

use App\Flare\Models\ItemAffix;
use App\Http\Controllers\Controller;

class AffixesController extends Controller {

    public function index() {
        return view('admin.affixes.affixes', [
            'affixes' => ItemAffix::all(),
        ]);
    }

    public function create() {
        return view('admin.affixes.manage', [
            'itemAffix' => null,
        ]);
    }

    public function show(ItemAffix $affix) {

        return view('admin.affixes.affix', [
            'itemAffix' => $affix,
        ]);
    }

    public function edit(ItemAffix $affix) {
        return view('admin.affixes.manage', [
            'itemAffix' => $affix,
        ]);
    }

    public function delete(ItemAffix $affix) {
        
    }
}
