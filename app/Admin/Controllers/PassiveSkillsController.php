<?php

namespace App\Admin\Controllers;

use App\Flare\Models\PassiveSkill;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Requests\NpcsImportRequest;
use App\Http\Controllers\Controller;
use App\Admin\Exports\Npcs\NpcsExport;
use App\Admin\Import\Npcs\NpcsImport;

class PassiveSkillsController extends Controller {

    public function index() {
        return view('admin.passive-skills.index');
    }

    public function show(PassiveSkill $passiveSkill) {
        return view('admin.passive-skill.show', [
            'skill' => $passiveSkill->with('child_skill'),
        ]);
    }

    public function create() {
        return view('admin.passive-skill.manage', [
            'skill'   => null,
            'editing' => false,
        ]);
    }

    public function edit(PassiveSkill $passiveSkill) {
        return view('admin.passive-skill.manage', [
            'skill'   => $passiveSkill,
            'editing' => true,
        ]);
    }

    public function exportNpcs() {
        return view('admin.passive-skill.export');
    }

    public function importNpcs() {
        return view('admin.passive-skill.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new NpcsExport(), 'passive_skills.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(NpcsImportRequest $request) {
        Excel::import(new NpcsImport, $request->npcs_import);

        return redirect()->back()->with('success', 'imported npc data.');
    }
}
