<?php

namespace App\Admin\Exports\ClassSpecials\Sheets;

use App\Flare\Models\GameClassSpecial;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassSpecialsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {

        $data = GameClassSpecial::all();

        return view('admin.exports.class-specials.sheets.class-specials', [
            'classSpecials' => $data,
        ]);
    }

    public function title(): string
    {
        return 'Class Specials';
    }
}
