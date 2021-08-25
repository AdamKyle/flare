<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Items\ItemsExport;
use App\Admin\Import\Items\ItemsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Http\Controllers\Controller;
use App\Admin\Requests\ItemsImport as ItemsImportRequest;
use App\Admin\Exports\Items\QuestsExport;
use App\Admin\Import\Items\QuestsImport;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;

class ItemsController extends Controller {

    use ItemsShowInformation;

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

    public function show(Item $item) {
        return $this->renderItemShow('game.items.item', $item);
    }

    public function edit(Item $item) {
        return view('admin.items.manage', [
            'item' => $item,
            'editing' => true,
        ]);
    }

    public function exportItems() {
        return view('admin.items.export');
    }

    public function importItems() {
        return view('admin.items.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export(Request $request) {
        $response = Excel::download(new ItemsExport($request->has('affixes')), 'items.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(ItemsImportRequest $request) {
        Excel::import(new ItemsImport, $request->items_import);

        return redirect()->back()->with('success', 'imported item data.');
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
