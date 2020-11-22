<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\GameSkill;

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
            'skill' => null,
        ]);
    }

    public function edit(GameSkill $skill) {
        return view('admin.skills.manage', [
            'skill' => $skill,
        ]);
    }
}
