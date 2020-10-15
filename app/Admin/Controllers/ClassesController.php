<?php

namespace App\Admin\Controllers;

use App\Flare\Models\GameClass;
use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Http\Controllers\Controller;

class ClassesController extends Controller {

    public function index() {
        return view('admin.classes.list');
    }

    public function show(GameClass $class) {
        return view('admin.classes.class', [
            'class' => $class,
        ]);
    }

    public function create() {
        return view('admin.classes.manage', [
            'class' => null,
        ]);
    }

    public function edit(GameClass $class) {
        return view('admin.classes.manage', [
            'class' => $class,
        ]);
    }
}
