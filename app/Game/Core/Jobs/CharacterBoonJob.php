<?php

namespace App\Game\Core\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\GameSkill;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Core\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Services\UseItemService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\CharacterBoon;
use App\Flare\Transformers\CharacterAttackTransformer;

class CharacterBoonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CharacterBoon $characterBoon
     */
    protected $characterBoon;

    /**
     * Create a new job instance.
     *
     * @param CharacterBoon $characterBoon
     */
    public function __construct(int $characterBoonId)
    {
        $this->characterBoon = $characterBoonId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UseItemService $useItemService)
    {
        $boon = CharacterBoon::find($this->characterBoon);

        if (is_null($boon)) {
            return;
        }

        $character = $boon->character;

        $boon->delete();

        $useItemService->updateCharacter($character->refresh());
    }
}
