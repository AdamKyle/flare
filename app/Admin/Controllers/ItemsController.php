<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Items\ItemsExport;
use App\Admin\Import\Items\ItemsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Admin\Exports\Items\ClassesExport;
use App\Admin\Import\Items\ClassImport;
use App\Admin\Requests\ItemsManagementRequest;
use App\Admin\Services\ItemsService;
use App\Flare\Values\ItemSpecialtyType;
use App\Admin\Requests\ItemsImport as ItemsImportRequest;
use App\Admin\Exports\Items\QuestsExport;
use App\Admin\Import\Items\QuestsImport;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;

class ItemsController extends Controller {

    use ItemsShowInformation;

    /**
     * @var ItemsService $itemService
     */
    private ItemsService $itemService;

    public function __construct(ItemsService $itemService) {
        $this->itemService = $itemService;
    }

    public function index() {
        return view('admin.items.items');
    }

    public function create() {
        return view('admin.items.manage', array_merge([
            'item' => null,
        ], $this->itemService->formInputs()));
    }

    public function edit(Item $item) {
        return view('admin.items.manage', array_merge([
            'item'             => $item,
        ], $this->itemService->formInputs()));
    }

    public function show(Item $item) {
        return $this->renderItemShow('game.items.item', $item);
    }

    public function store(ItemsManagementRequest $request) {
        $data = $this->itemService->cleanRequestData($request->all());

        $item = Item::updateOrCreate(['id' => $request->id], $data);

        $message = 'Created ' . $item->name;

        if ($request->id !== 0) {
            $message = 'Updated ' . $item->name;
        }

        return response()->redirectToRoute('game.items.item', ['item' => $item->id])->with('success', $message);
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
            'artifact' => [
                'artifact',
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
            ],
            'specialty-shops' => [
                ItemSpecialtyType::HELL_FORGED,
                ItemSpecialtyType::PURGATORY_CHAINS,
                ItemSpecialtyType::PIRATE_LORD_LEATHER,
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
