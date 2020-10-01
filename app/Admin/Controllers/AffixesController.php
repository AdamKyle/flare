<?php

namespace App\Admin\Controllers;

use App\Admin\Services\ItemAffixService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

    public function delete(Request $request, ItemAffixService $itemAffixService, ItemAffix $affix) {
        $name = $affix->name;

        $itemAffixService->deleteAffix($affix);

        return redirect()->back()->with('success', $name . ' was deleated successfully.');
    }
}
