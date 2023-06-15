<?php

namespace App\Admin\Exports\Events\Sheets;

use Illuminate\Contracts\View\View;
use App\Flare\Models\ScheduledEvent;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EventsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.events.sheets.scheduled-events', [
            'events' => ScheduledEvent::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Scheduled Events';
    }
}
