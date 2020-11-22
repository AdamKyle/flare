<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\GameClass;

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
