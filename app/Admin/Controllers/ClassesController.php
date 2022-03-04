<?php

namespace App\Admin\Controllers;

use App\Game\Core\Values\View\ClassBonusInformation;
use App\Http\Controllers\Controller;
use App\Flare\Models\GameClass;

class ClassesController extends Controller {

    public function index() {
        return view('admin.classes.list');
    }

    public function show(GameClass $class) {
        return view('admin.classes.class', [
            'class' => $class,
            'classBonus' => (new ClassBonusInformation())->buildClassBonusDetailsForInfo($class->name),
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
