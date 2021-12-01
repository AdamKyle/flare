<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\PassiveSkills\PassiveSkillsExport;
use App\Admin\Import\PassiveSkills\PassiveSkillsImport;
use App\Admin\Requests\ManagePassiveSkillRequest;
use App\Admin\Requests\PassivesImportRequest;
use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;

class PassiveSkillsController extends Controller {

    public function index() {
        return view('admin.passive-skills.index');
    }

    public function show(PassiveSkill $passiveSkill) {
        return view('admin.passive-skills.show', [
            'skill' => $passiveSkill,
        ]);
    }

    public function create() {
        return view('admin.passive-skills.manage', [
            'skill'   => null,
            'editing' => false,
            'effects' => PassiveSkillTypeValue::getNamedValues(),
            'parentSkills' => PassiveSkill::pluck('name', 'id')->toArray(),
        ]);
    }

    public function edit(PassiveSkill $passiveSkill) {
        return view('admin.passive-skills.manage', [
            'skill'   => $passiveSkill,
            'editing' => true,
            'effects' => PassiveSkillTypeValue::getNamedValues(),
            'parentSkills' => PassiveSkill::pluck('name', 'id')->toArray(),
        ]);
    }

    public function store(ManagePassiveSkillRequest $request) {
        $data = $request->all();

        $data['is_locked'] = $request->has('is_locked');
        $data['is_parent'] = $request->has('is_Parent');

        $passiveSkill = PassiveSkill::create($data);

        return response()->redirectTo(route('passive.skills.skill', [
            'passiveSkill' => $passiveSkill->id,
        ]))->with('success', 'Created: ' . $passiveSkill->name);
    }

    public function update(ManagePassiveSkillRequest $request, PassiveSkill $passiveSkill) {
        $data = $request->all();

        $data['is_locked'] = $request->has('is_locked');
        $data['is_parent'] = $request->has('is_Parent');

        $passiveSkill->update($data);

        $passiveSkill = $passiveSkill->refresh();

        return response()->redirectTo(route('passive.skills.skill', [
            'passiveSkill' => $passiveSkill->id,
        ]))->with('success', 'Updated: ' . $passiveSkill->name);
    }

    public function exportPassives() {
        return view('admin.passive-skills.export');
    }

    public function importPassives() {
        return view('admin.passive-skills.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export() {
        $response = Excel::download(new PassiveSkillsExport(), 'passive_skills.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(PassivesImportRequest $request) {
        Excel::import(new PassiveSkillsImport(), $request->passives_import);

        return redirect()->back()->with('success', 'Imported passive skill data.');
    }
}
