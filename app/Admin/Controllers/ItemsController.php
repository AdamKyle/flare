<?php

namespace App\Admin\Controllers;

use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ItemsController extends Controller {

    public function index() {
        return view('admin.items.items', [
            'items' => Item::all(),
        ]);
    }

    public function create() {
        return view('admin.items.manage', [
            'item' => null,
            'editing' => false,
        ]);
    }

    public function edit(Item $item) {
        return view('admin.items.manage', [
            'item' => $item,
            'editing' => true,
        ]);
    }

    public function delete(Item $item) {
        $slots = InventorySlot::where('item_id', $item->id)->get();
        $name  = $item->affix_name;

        if ($slots->isEmpty()) {
            $item->delete();

            return redirect()->back()->with('success', $name . ' was deleted successfully.');
        }

        foreach($slots as $slot) {
            $character = $slot->inventory->character;

            $slot->delete();

            $gold = SellItemCalculator::fetchTotalSalePrice($item);

            $character->gold += $gold;
            $character->save();

            $character = $character->refresh();

            event(new ServerMessageEvent($character->user, 'deleted_item', $name));
            event(new UpdateTopBarEvent($character));
        }

        $item->delete();

        return redirect()->back()->with('success', $name . ' was deleted successfully.');
    }

    public function deleteAll(Request $request) {
        foreach($request->items as $item) {
            $item  = Item::find($item);

            if (is_null($item)) {
                return redirect()->back()->with('error', 'Invalid input.');
            }

            $slots = InventorySlot::where('item_id', $item->id)->get();
            $name  = $item->affix_name;

            if ($slots->isEmpty()) {
                $item->delete();
            }

            foreach($slots as $slot) {
                $character = $slot->inventory->character;

                $slot->delete();

                $gold = SellItemCalculator::fetchTotalSalePrice($item);

                $character->gold += $gold;
                $character->save();

                $character = $character->refresh();

                event(new ServerMessageEvent($character->user, 'deleted_item', $name));
                event(new UpdateTopBarEvent($character));
            }

            $item->delete();
        }

        return redirect()->back()->with('success', 'Deleted all selected items');
    }
}
