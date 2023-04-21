<?php

namespace App\Admin\Exports\Monsters\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\Monster;

class MonstersSheet implements FromView, WithTitle, ShouldAutoSize {

    private string $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.monsters.sheets.monsters', [
            'monsters' => Monster::orderBy('game_map_id')->where('is_celestial_entity', ($this->type === 'celestial'))->get(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Monsters';
    }
}
