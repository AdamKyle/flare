<?php

namespace App\Admin\Controllers;

use App\Game\Core\Values\View\ClassBonusInformation;
use App\Http\Controllers\Controller;
use App\Flare\Models\GameClass;
use Illuminate\Http\Request;

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

    public function store(Request $request) {
        $class = GameClass::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('classes.class', ['class' => $class->id])->with('success', 'Class has been saved.');
    }

    public function edit(GameClass $class) {
        return view('admin.classes.manage', [
            'class' => $class,
            'stats' => ['str', 'dex', 'agi', 'int', 'focus', 'chr', 'dur'],
        ]);
    }
}
