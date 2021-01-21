<?php

namespace App\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Game\Core\Services\CharacterService;

class LevelTestCharacter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $character;

    /**
     * @var int $levelTo
     */
    public $levelTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, int $levelTo = 1000) {
        $this->character = $character;
        $this->levelTo = env('APP_ENV') === 'testing' ? 2 : $levelTo;
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
    }
}
