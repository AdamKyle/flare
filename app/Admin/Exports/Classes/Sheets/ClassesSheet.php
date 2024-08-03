<?php

namespace App\Admin\Exports\Classes\Sheets;

use App\Flare\Models\GameClass;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassesSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {

        return view('admin.exports.classes.sheets.classes', [
            'gameClasses' => GameClass::all(),
        ]);
    }

    public function title(): string
    {
        return 'Game Clases';
    }
}
