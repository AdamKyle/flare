<?php

namespace App\Admin\Exports\Classes\Sheets;

use App\Flare\Models\GameClass;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassesSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {


        return view('admin.exports.classes.sheets.classes', [
            'gameClasses' => GameClass::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Game Clases';
    }
}
