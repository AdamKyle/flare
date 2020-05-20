<?php

namespace App\Game\Core\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ItemComparisonController extends Controller {


    public function __construct() {
        $this->middleware('auth:api');
    }

    public function comparison(Request $request) {
        dd($request->all());
    }
}
