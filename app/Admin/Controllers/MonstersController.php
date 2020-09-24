<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Monster;

class MonstersController extends Controller {

    public function __construct() {
        //
    }

    public function index() {
        return view('admin.monsters.monsters', [
            'adventures' => Monster::all(),
        ]);
    }

    public function show(Monster $monster) {
        return view('admin.monsters.monster', [
            'monster' => $monster,
        ]);
    }

    public function create() {
        return view('admin.monsters.manage', [
            'monster' => null
        ]);
    }

    public function edit(Monster $monster) {
        return view('admin.monsters.manage', [
            'monster' => $monster,
        ]);
    }
}
