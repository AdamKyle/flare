<?php

namespace Tests\Feature\Admin\Charts;

use App\Flare\Mail\GenericMail;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BattleSimmulationChartTest extends TestCase {

    use RefreshDatabase;
}
