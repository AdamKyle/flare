<?php

namespace App\Admin\Exports\LocationTemplates\Sheets;

use App\Flare\Models\LocationTemplate;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationTemplatesSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.location-templates.sheets.location-templates', [
            'locationTemplates' => LocationTemplate::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function title(): string
    {
        return 'Location Templates';
    }
}
