<?php

namespace App\Admin\Exports\Items\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\Item;

class ItemsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.items.sheets.items', [
            'items' => Item::whereNull('item_suffix_id')->whereNull('item_prefix_id')->orderBy('type', 'desc')->orderBy('cost', 'asc')->get(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Items';
    }
}
