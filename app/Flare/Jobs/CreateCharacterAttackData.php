<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterAttackTransformer;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCharacterAttackData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Kingdom $user
     */
    public $characterId;

    /**
     * Create a new job instance.
     *
     * @param Kingdom $kingdom
     */
    public function __construct(int $characterId) {
        $this->characterId = $characterId;
    }

    public function handle() {
        $this->updateCharacterAttakDataCache(Character::find($this->characterId));
    }

    protected function updateCharacterAttakDataCache(Character $character) {
        $characterData = new ResourceItem($character, new CharacterAttackTransformer);

        $manager = new Manager();

        Cache::put('characterData-' . $character->id, $manager->createData($characterData)->toArray());
    }
}
