<?php

namespace App\Admin\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Flare\Models\GameSkill;
use App\Admin\Exports\Skills\SkillsExport;
use App\Admin\Requests\SkillsImport;
use App\Admin\Import\Skills\SkillsImport as ExcelImportSkills;

class SkillsController extends Controller {

    public function index() {
        return view('admin.skills.skills');
    }

    public function show(GameSkill $skill) {
        return view('admin.skills.skill', [
            'skill' => $skill,
        ]);
    }

    public function create() {
        return view('admin.skills.manage', [
            'skill'   => null,
            'editing' => false,
        ]);
    }

    public function edit(GameSkill $skill) {
        return view('admin.skills.manage', [
            'skill'   => $skill,
            'editing' => true,
        ]);
    }

    public function exportSkills() {
        return view('admin.skills.export');
    }

    public function importSkills() {
        return view('admin.skills.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new SkillsExport, 'skills.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(SkillsImport $request) {
        Excel::import(new ExcelImportSkills, $request->skills_import, null, \Maatwebsite\Excel\Excel::XLSX);

        return redirect()->back()->with('success', 'imported skills data.');
    }
}
