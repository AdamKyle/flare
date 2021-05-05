<?php

namespace App\Admin\Jobs;

use App\Flare\Mail\GenericMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\User;
use App\Game\Core\Services\CharacterService;
use Cache;
use Mail;

class LevelTestCharacter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * @var int $levelTo
     */
    public $levelTo;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var bool
     */
    public $updatingRace;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, int $levelTo = 1000, User $user = null, bool $testUpdatingRace = false) {
        $this->character    = $character;
        $this->levelTo      = env('APP_ENV') === 'testing' ? 2 : $levelTo;
        $this->user         = $user;
        $this->updatingRace = $testUpdatingRace;
    }

    /**
     *
     * @return void
     */
    public function handle() {

        foreach ($this->character->snapShots as $snapShot) {
            $snapShot->delete();
        }

        CharacterSnapShot::create([
            'character_id' => $this->character->id,
            'snap_shot'    => $this->character->getAttributes(),
        ]);

        for ($i = 1; $i < $this->levelTo; $i++) {
            $characterService = new CharacterService;

            $characterService->levelUpCharacter($this->character);

            $character = $this->character->refresh();

            CharacterSnapShot::create([
                'character_id' => $character->id,
                'snap_shot'    => $character->getAttributes(),
            ]);
        }

        if (!is_null($this->user)) {
            $this->updateCache($character);
        }
    }

    protected function updateCache(Character $character) {
        $lastCharacter = null;
        $message       = null;
        $title         = null;

        if ($this->updatingRace) {
            $lastCharacter = Character::where('game_race_id', $character->race->id)->orderBy('id', 'desc')->first();
            $message       = 'All test characters racial attributes have been updated.';
            $title         = 'Test Character Racial Attribute Update Complete.';
        } else {
            $lastCharacter = Character::where('game_class_id', $character->class->id)->orderBy('id', 'desc')->first();
            $message       = 'All test characters class attributes have been updated.';
            $title         = 'Test Character Class Attribute Update Complete.';
        }

        if (!is_null($lastCharacter)) {
            if ($lastCharacter->id === $character->id && Cache::has('updating-test-characters')) {
                Cache::delete('updating-test-characters');

                Mail::to($this->user->email)->send(new GenericMail($this->user, $message, $title));
            }
        }
    }
}
