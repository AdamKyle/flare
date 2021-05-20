<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Kingdoms\KingdomsExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class KingdomsController extends Controller {

    public function index() {
        return view('admin.kingdoms.export');
    }

    public function export() {
        $response = Excel::download(new KingdomsExport, 'kingdoms.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }
}
