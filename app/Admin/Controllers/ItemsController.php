<?php

namespace App\Admin\Controllers;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Http\Controllers\Controller;

class ItemsController extends Controller {

    public function index() {
        return view('admin.items.items', [
            'items' => Item::all(),
        ]);
    }

    public function create() {
        return view('admin.items.manage', [
            'item' => null,
        ]);
    }

    public function edit(Item $item) {
        return view('admin.items.manage', [
            'item' => $item,
        ]);
    }

    public function delete(Item $item) {
        $slots = InventorySlot::where('item_id', $item->id)->all();
        $name  = $item->name;

        if ($slots->isEmpty()) {
            $item->delete();

            return redirect()->back()->with('success', $name . ' was deleted successfully.');
        }

        foreach($slots as $slot) {
            $character = $slot->inventory->character;

            $slot->delete();

            $character->gold += $item->cost;
            $character->save();

            $character = $character->refresh();

            event(new ServerMessageEvent($character->user, 'deleted_item', $name));
            event(new UpdateTopBarEvent($character));
        }
    }
}
