<?php

namespace App\Admin\Items\Exports\Sheets;

use App\Flare\Models\Item;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ItemsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * @param  array<int, string>  $itemTypes  Item `type`/`specialty_type` family values to export.
     */
    public function __construct(private readonly array $itemTypes = []) {}

    /**
     * Build the export view with the requested catalog Item family.
     *
     * @return View Items workbook export view.
     */
    public function view(): View
    {
        $query = Item::whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('parent_id');

        if (empty($this->itemTypes)) {
            $items = $query->orderBy('type', 'desc')->orderBy('cost', 'asc')->get();
        } else {
            $items = $query->where(function ($familyQuery) {
                $familyQuery->whereIn('type', $this->itemTypes)
                    ->orWhereIn('specialty_type', $this->itemTypes);
            })
                ->orderBy('type', 'desc')
                ->orderBy('cost', 'asc')
                ->get()
                ->unique('name');
        }

        return view('admin.items.exports.sheets.items', [
            'items' => $items,
        ]);
    }

    /**
     * Return the Items workbook sheet title.
     *
     * @return string Items workbook sheet title.
     */
    public function title(): string
    {
        return 'Items';
    }
}
