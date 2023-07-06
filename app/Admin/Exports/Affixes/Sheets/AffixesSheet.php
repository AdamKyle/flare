<?php

namespace App\Admin\Exports\Affixes\Sheets;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;

class AffixesSheet implements FromView, WithTitle, ShouldAutoSize {

    private $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    /**
     * @return View
     */
    public function view(): View {
        $query = (new ItemAffixType(intval($this->type)))->query(ItemAffix::query());

        return view('admin.exports.affixes.sheets.affixes', [
            'affixes' => $query->where('randomly_generated', false)->orderBy('skill_level_required', 'asc')->get(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Affixes';
    }
}
