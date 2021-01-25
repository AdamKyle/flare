<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Admin\Services\ItemAffixService;
use App\Flare\Models\ItemAffix;

class AffixesController extends Controller {

    public function index() {
        return view('admin.affixes.affixes', [
            'affixes' => ItemAffix::all(),
        ]);
    }

    public function create() {
        return view('admin.affixes.manage', [
            'itemAffix' => null,
            'editing'   => false,
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
            'editing'   => true,
        ]);
    }

    public function delete(Request $request, ItemAffixService $itemAffixService, ItemAffix $affix) {
        $name = $affix->name;

        $itemAffixService->deleteAffix($affix);

        return redirect()->back()->with('success', $name . ' was deleated successfully.');
    }
}
