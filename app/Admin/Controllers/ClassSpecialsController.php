<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\ClassSpecials\ClassSpecialsExport;
use App\Admin\Import\ClassSpecials\ClassSpecialsImport;
use App\Admin\Requests\ClassSpecialsImportRequest;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameClassSpecial;
use App\Game\Core\Combat\Values\AttackType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClassSpecialsController extends Controller
{
    public function index()
    {
        return view('admin.class-specials.list');
    }

    public function show(GameClassSpecial $gameClassSpecial)
    {
        return view('admin.class-specials.show', [
            'classSpecial' => $gameClassSpecial,
        ]);
    }

    public function create()
    {
        return view('admin.class-specials.manage', [
            'classSpecial' => null,
            'gameClasses' => GameClass::pluck('name', 'id')->toArray(),
            'forAttackType' => [
                AttackType::ATTACK->value => AttackType::ATTACK->value,
                AttackType::CAST->value => AttackType::CAST->value,
                AttackType::ATTACK_AND_CAST->value => AttackType::ATTACK_AND_CAST->value,
                AttackType::CAST_AND_ATTACK->value => AttackType::CAST_AND_ATTACK->value,
                AttackType::DEFEND->value => AttackType::DEFEND->value,
                'any' => 'any',
            ],
        ]);
    }

    public function store(Request $request)
    {
        GameClassSpecial::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('class-specials.list')->with('success', $request->name.' has been created!');
    }

    public function edit(GameClassSpecial $gameClassSpecial)
    {
        return view('admin.class-specials.manage', [
            'classSpecial' => $gameClassSpecial,
            'gameClasses' => GameClass::pluck('name', 'id')->toArray(),
            'forAttackType' => [
                AttackType::ATTACK->value => AttackType::ATTACK->value,
                AttackType::CAST->value => AttackType::CAST->value,
                AttackType::ATTACK_AND_CAST->value => AttackType::ATTACK_AND_CAST->value,
                AttackType::CAST_AND_ATTACK->value => AttackType::CAST_AND_ATTACK->value,
                AttackType::DEFEND->value => AttackType::DEFEND->value,
                'any' => 'any',
            ],
        ]);
    }

    public function showExport()
    {
        return view('admin.class-specials.export');
    }

    public function showImport()
    {
        return view('admin.class-specials.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export()
    {
        $response = Excel::download(new ClassSpecialsExport, 'class-specials.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(ClassSpecialsImportRequest $request)
    {
        Excel::import(new ClassSpecialsImport, $request->class_specials_import);

        return redirect()->back()->with('success', 'imported class specials.');
    }
}
