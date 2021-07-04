<?php

namespace App\Game\Core\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\UpdateAttackStats;
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
    public function __construct(CharacterBoon $characterBoon)
    {
        $this->characterBoon = $characterBoon;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CharacterAttackTransformer $characterAttackTransformer, Manager $manager)
    {
        $character = $this->characterBoon->character;

        $this->characterBoon->delete();

        $character = $character->refresh();

        $characterAttack = new Item($character, $characterAttackTransformer);

        event(new UpdateAttackStats($manager->createData($characterAttack)->toArray(), $character->user));
        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'A boon has worn off. Your stats have been updated.'));
    }
}
