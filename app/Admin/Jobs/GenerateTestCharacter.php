<?php

namespace App\Admin\Jobs;

use App\Admin\Mail\GenericMail;
use App\Admin\Services\AssignSkillService;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use App\Game\Core\Services\CharacterService;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Str;

class GenerateTestCharacter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var GameRace $race
     */
    public $race;

    /**
     * @var GameClass $class
     */
    public $class;

    /**
     * @var User $adminUser
     */
    public $adminUser;

    /**
     * @var int $levelTo
     */
    public $levelTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GameRace $race, GameClass $class, User $adminUser = null, int $levelTo = 1000) {
        $this->race = $race;
        $this->class = $class;
        $this->adminUser = $adminUser;
        $this->levelTo = $levelTo;
    }

    /**
     * Attempt to assign the skill.
     * 
     * If the skill should fail to be asigned to the intended target for any reason, we email
     * the administrator with the error message.
     *
     * @param AssignSkillService $service
     * @return void
     */
    public function handle(AssignSkillService $service) {
        $map = GameMap::where('default', true)->first(); 

        $user = User::factory()->create([
            'is_test'    => true,
            'ip_address' => null
        ]);

        $character = (new CharacterBuilder)->setRace($this->race)
                              ->setClass($this->class)
                              ->createCharacter($user, $map, 'test' . Str::random(11))
                              ->assignSkills()
                              ->character();

        CharacterSnapShot::create([
            'character_id' => $character->id,
            'snap_shot'    => $character->getAttributes(),
        ]);

        for ($i = 1; $i < $this->levelTo; $i++) {
            $characterService = new CharacterService;

            $characterService->levelUpCharacter($character);

            $character = $character->refresh();

            CharacterSnapShot::create([
                'character_id' => $character->id,
                'snap_shot'    => $character->getAttributes(),
            ]);
        }

        if (!is_null($this->adminUser)) {
            Cache::forget('generating-characters');
            
            Mail::to($this->adminUser->email)->send(new GenericMail($this->adminUser, 'Your character modeling generation is done.', 'Character modeling complete.', false));
        }
    }
}
