<?php

namespace App\Admin\Exports\Items\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\Item;
use phpDocumentor\Reflection\Types\Boolean;

class ItemsSheet implements FromView, WithTitle, ShouldAutoSize {

    private bool $affixesOnly = false;

    public function __construct(bool $affixesOnly) {
        $this->affixesOnly = $affixesOnly;
    }

    /**
     * @return View
     */
    public function view(): View {
        $items = Item::whereNull('item_suffix_id')->whereNull('item_prefix_id')->orderBy('type', 'desc')->orderBy('cost', 'asc')->get();

        if ($this->affixesOnly) {
            $items = Item::whereNotNull('item_suffix_id')->orWhereNotNull('item_prefix_id')->orderBy('type', 'desc')->orderBy('cost', 'asc')->get();
        }

        return view('admin.exports.items.sheets.items', [
            'items' => $items
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Items';
    }
}
