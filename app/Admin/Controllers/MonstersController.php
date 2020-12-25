<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Monster;
use Cache;

class MonstersController extends Controller {

    public function __construct() {
        $this->middleware('is.admin')->except([
            'show'
        ]);
    }

    public function index() {
        return view('admin.monsters.monsters', [
            'isProcessingBattle' => Cache::has('processing-battle'),
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
