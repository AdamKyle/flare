<?php

namespace App\Admin\Exports\Items\Sheets;

use App\Flare\Models\Item;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ItemsSheet implements FromView, ShouldAutoSize, WithTitle
{
    private array $itemTypes;

    public function __construct(array $itemTypes = [])
    {
        $this->itemTypes = $itemTypes;
    }

    public function view(): View
    {
        if (empty($this->itemTypes)) {
            $items = Item::whereNull('item_suffix_id')->whereNull('item_prefix_id')->orderBy('type', 'desc')->orderBy('cost', 'asc')->get();
        } else {
            $items = Item::whereNull('item_suffix_id')->whereNull('item_prefix_id')->whereIn('type', $this->itemTypes)->orWhereIn('specialty_type', $this->itemTypes)->orderBy('type', 'desc')->orderBy('cost', 'asc')->get()->unique('name');
        }

        return view('admin.exports.items.sheets.items', [
            'items' => $items,
        ]);
    }

    public function title(): string
    {
        return 'Items';
    }
}
