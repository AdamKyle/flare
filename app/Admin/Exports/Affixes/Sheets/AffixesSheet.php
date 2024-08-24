<?php

namespace App\Admin\Exports\Affixes\Sheets;

use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class AffixesSheet implements FromView, ShouldAutoSize, WithTitle
{
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function view(): View
    {
        $query = (new ItemAffixType(intval($this->type)))->query(ItemAffix::query());

        return view('admin.exports.affixes.sheets.affixes', [
            'affixes' => $query->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get(),
        ]);
    }

    public function title(): string
    {
        return 'Affixes';
    }
}
