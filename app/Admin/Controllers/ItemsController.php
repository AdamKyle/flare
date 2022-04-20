<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Items\ItemsExport;
use App\Admin\Import\Items\ItemsImport;
use App\Admin\Services\ItemsService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Http\Controllers\Controller;
use App\Admin\Requests\ItemsImport as ItemsImportRequest;
use App\Admin\Exports\Items\QuestsExport;
use App\Admin\Import\Items\QuestsImport;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;

class ItemsController extends Controller {

    use ItemsShowInformation;

    public function index() {
        return view('admin.items.items');
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
            'item'             => $item,
            'defaultPositions' => [
                'bow',
                'body',
                'leggings',
                'feet',
                'sleeves',
                'helmet',
                'gloves',
            ],
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
        $types = [
            'weapons' => [
                'weapon', 'bow', 'hammer', 'stave'
            ],
            'armour' => [
                'helmet', 'body', 'leggings', 'sleeves', 'feet', 'shield', 'gloves'
            ],
            'spells' => [
                'spell-damage', 'spell-healing',
            ],
            'rings' => [
                'ring'
            ],
            'quest' => [
                'quest'
            ],
            'alchemy' => [
                'alchemy'
            ],
            'artifacts' => [
                'artifact'
            ],
            'trinket' => [
                'trinket'
            ]
        ];

        $response = Excel::download(new ItemsExport($types[$request->type_to_export]), 'items.xlsx', \Maatwebsite\Excel\Excel::XLSX);
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

    public function delete(Item $item, ItemsService $itemsService) {
        $response = $itemsService->deleteItem($item);

        return redirect()->back()->with('success', $response['message']);
    }

    public function deleteAll(Request $request, ItemsService $itemsService) {
        foreach($request->items as $item) {
            $item  = Item::find($item);

            if (is_null($item)) {
                return redirect()->back()->with('error', 'Invalid input.');
            }

            $itemsService->deleteItem($item);
        }

        return redirect()->back()->with('success', 'Deleted all selected items');
    }
}
