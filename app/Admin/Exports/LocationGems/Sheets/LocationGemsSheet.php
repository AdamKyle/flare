<?php

namespace App\Admin\Exports\LocationGems\Sheets;

use App\Flare\Models\GameLocationGemParamters;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationGemsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.location-gems.sheets.location-gems', [
            'locationGems' => GameLocationGemParamters::with('location.map')->get(),
        ]);
    }

    public function title(): string
    {
        return 'Location Gems';
    }
}
