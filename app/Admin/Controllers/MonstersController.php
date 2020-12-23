<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Monster;

class MonstersController extends Controller {

    public function __construct() {
        $this->middleware('is.admin')->except([
            'show'
        ]);
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

    public function publish(Monster $monster) {
        $monster->update(['published' => true]);

        return redirect()->to(route('monsters.list'))->with('success', 'Monster was published.');
    }
}
