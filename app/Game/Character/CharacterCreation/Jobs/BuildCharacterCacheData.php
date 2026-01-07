<?php

namespace App\Game\Character\CharacterCreation\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Pipeline\Steps\BuildCache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\SimpleCache\InvalidArgumentException;

class BuildCharacterCacheData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $characterId) {}

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function handle(BuildCache $buildCache): void
    {

        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $buildCache->process($character);
    }
}
