<?php

namespace App\Admin\Controllers;

use App\Admin\Mail\ResetPasswordEmail;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mail;

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
