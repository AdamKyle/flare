<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Kingdoms\KingdomsExport;
use App\Admin\Import\Kingdoms\KingdomsImport;
use App\Admin\Requests\KingdomImport;
use App\Flare\Models\GameBuilding;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class KingdomsController extends Controller {

    public function index() {
        return view('admin.kingdoms.export');
    }

    public function import() {
        return view('admin.kingdoms.import');
    }

    public function export() {
        $response = Excel::download(new KingdomsExport, 'kingdoms.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    public function importData(KingdomImport $request) {

        if (GameBuilding::count() > 1) {
            return redirect()->back()->with('error', 'You already have data in the system. Import aborted.');
        }

        Excel::import(new KingdomsImport(), $request->kingdom_import);

        return redirect()->back()->with('success', 'imported kingdom data.');
    }
}
