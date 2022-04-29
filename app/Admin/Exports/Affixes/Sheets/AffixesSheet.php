<?php

namespace App\Admin\Exports\Affixes\Sheets;


use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\ItemAffix;

class AffixesSheet implements FromView, WithTitle, ShouldAutoSize {

    private $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    /**
     * @return View
     */
    public function view(): View {

        $query = $this->createQuery(ItemAffix::query(), $this->type);

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

    protected function createQuery(Builder $query, string $type): Builder {
        $invalidField = '0';
        $damageField  = 'damage';
        $booleanFields = ['irresistible_damage', 'damage_can_stack'];

        if ($invalidField === $type) {
            return $query;
        }

        if ($damageField === $type) {
            return $query->where($type, '>', 0);
        }

        if (in_array($type, $booleanFields)) {
            return $query->where($type, true);
        }

        if (preg_match('/_/', $type)) {
            return $query->where($type, '>', 0);
        }

        return $query->where('skill_name', $type);
    }
}
