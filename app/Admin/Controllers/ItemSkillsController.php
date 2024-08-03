<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\ItemSkills\ItemSkillsExport;
use App\Admin\Requests\ItemSkillManagementRequest;
use App\Admin\Requests\ItemSkillsImport;
use App\Flare\Models\ItemSkill;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ItemSkillsController extends Controller
{
    public function index()
    {
        return view('admin.item-skills.skills');
    }

    public function create()
    {
        return view('admin.item-skills.manage', [
            'itemSkill' => null,
            'parentSkills' => ItemSkill::pluck('name', 'id'),
        ]);
    }

    public function edit(ItemSkill $itemSkill)
    {
        return view('admin.item-skills.manage', [
            'itemSkill' => $itemSkill,
            'parentSkills' => ItemSkill::pluck('name', 'id'),
        ]);
    }

    public function show(ItemSkill $itemSkill)
    {
        return view('admin.item-skills.skill', ['itemSkill' => $itemSkill]);
    }

    public function store(ItemSkillManagementRequest $request)
    {

        $itemSkill = ItemSkill::find($request->id);

        if (! is_null($itemSkill)) {
            $itemSkill->update($request->all());
        } else {
            $itemSkill = ItemSkill::create($request->all());
        }

        $message = 'Created '.$itemSkill->name;

        if ($request->id !== 0) {
            $message = 'Updated '.$itemSkill->name;
        }

        return response()->redirectToRoute('admin.items-skills.show', ['itemSkill' => $itemSkill->id])->with('success', $message);
    }

    public function exportItemSkills()
    {
        return view('admin.item-skills.export');
    }

    public function importItemSkills()
    {
        return view('admin.item-skills.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export()
    {

        $response = Excel::download(new ItemSkillsExport, 'item-skills.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function importData(ItemSkillsImport $request)
    {
        Excel::import(new ItemSkillsImport, $request->item_skills_import);

        return redirect()->back()->with('success', 'imported item skill data.');
    }
}
