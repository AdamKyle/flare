<?php

namespace App\Admin\Controllers;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
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
        $column = 'item_'.$affix->type.'_id';

        $itemsWithThisAffix = Item::where($column, $affix->id)->get();
        $name = $affix->name;

        if ($itemsWithThisAffix->isNotEmpty()) {
            foreach($itemsWithThisAffix as $item) {

                $slots = InventorySlot::where('item_id', $item->id)->get();
                
                $item->{$column} = null;
                $item->save();

                if (is_null($item->itemPrefix) && is_null($item->itemSuffix)) {
                    $total = Item::where('name', $item->name)->count();

                    if ($total > 1) {

                        if ($slots->isNotEmpty()) {
                            foreach ($slots as $slot) {
                                
                                $slot->update([
                                    'item_id' => Item::where('name', $item->name)
                                                     ->where('id', '!=', $item->id)
                                                     ->where('item_suffix_id', null)
                                                     ->where('item_prefix_id', null)
                                                     ->first()->id,
                                ]);
                            }
                        }
                    }

                    $item->delete();
                }

                if ($slots->isNotEmpty()) {
                    foreach ($slots as $slot) {
                        
                        $character = $slot->inventory->character;

                        $character->gold += $affix->cost;
                        $character->save();

                        $character = $character->refresh();

                        $forMessages = $name . ' has been removed from one or more of your items. You have been compensated the amount of: ' . $affix->cost;

                        event(new ServerMessageEvent($character->user, 'deleted_affix', $forMessages));
                        event(new UpdateTopBarEvent($character));
                    }
                }
            }
        }

        $affix->delete();

        return redirect()->back()->with('success', $name . ' deleated successfully.');
    }
}
