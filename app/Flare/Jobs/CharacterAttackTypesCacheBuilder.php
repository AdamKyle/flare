<?php

namespace App\Flare\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use App\Flare\Models\Character;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Traits\UpdateMarketBoard;

class CharacterAttackTypesCacheBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    /**
     * @var User $user
     */
    public $character;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes) {

        $buildCharacterAttackTypes->buildCache($this->character);

        event(new UpdateTopBarEvent($this->character));
    }
}
