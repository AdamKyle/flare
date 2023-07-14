<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Import\Events\EventsImport;
use App\Admin\Exports\Events\EventsExport;
use App\Admin\Requests\EventsImportRequest;

class EventsController extends Controller {

    public function exportEvents() {
        return view('admin.events.export');
    }

    public function importEvents() {
        return view('admin.events.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function exportInfo() {

        $response = Excel::download(new EventsExport(), 'scheduled_events.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importInfo(EventsImportRequest $request) {
        Excel::import(new EventsImport, $request->scheduled_events);

        return redirect()->back()->with('success', 'imported scheduled events data.');
    }
}
