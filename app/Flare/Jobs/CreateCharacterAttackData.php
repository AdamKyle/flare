<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Character;
use App\Flare\Services\BuildCharacterAttackTypes;
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

    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes) {
        $buildCharacterAttackTypes->buildCache(Character::find($this->characterId));
    }
}
