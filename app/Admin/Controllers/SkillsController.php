<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Skills\SkillsExport;
use App\Admin\Import\Skills\SkillsImport as ExcelImportSkills;
use App\Admin\Requests\SkillsImport;
use App\Admin\Services\AssignSkillService;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SkillsController extends Controller
{
    public function index()
    {
        return view('admin.skills.skills');
    }

    public function show(GameSkill $skill)
    {
        return view('admin.skills.skill', [
            'skill' => $skill,
        ]);
    }

    public function create()
    {
        return view('admin.skills.manage', [
            'skill' => null,
            'skillTypes' => SkillTypeValue::$namedValues,
            'gameClasses' => GameClass::pluck('name', 'id'),
        ]);
    }

    public function edit(GameSkill $skill)
    {
        return view('admin.skills.manage', [
            'skill' => $skill,
            'skillTypes' => SkillTypeValue::$namedValues,
            'gameClasses' => GameClass::pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $skill = GameSkill::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('skills.skill', ['skill' => $skill->id])->with('success', $skill->name.' was saved successfully.');
    }

    public function exportSkills()
    {
        return view('admin.skills.export');
    }

    public function importSkills()
    {
        return view('admin.skills.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export()
    {
        $response = Excel::download(new SkillsExport, 'skills.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(SkillsImport $request, AssignSkillService $assignSkillService)
    {
        Excel::import(new ExcelImportSkills, $request->skills_import, null, \Maatwebsite\Excel\Excel::XLSX);

        $assignSkillService->assignSkills();

        return redirect()->back()->with('success', 'imported skills data.');
    }
}
