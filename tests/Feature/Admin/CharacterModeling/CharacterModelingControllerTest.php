<?php

namespace Tests\Feature\Admin\CharacterModeling;

use App\Admin\Jobs\RunTestSimulation;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Queue;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateAdventure;

class CharacterModelingControllerTest extends TestCase
{
    use RefreshDatabase;
}
