<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CapitalCityUpdateAutoWalkedKingdoms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Kingdom $kingdom,
    ) {}

    public function handle(KingdomTransformer $kingdomTransformer, Manager $manager): void
    {
        $kingdomData = new Item($this->kingdom, $kingdomTransformer);
        $kingdomData = $manager->createData($kingdomData)->toArray();
        $user = $this->kingdom->character->user;

        event(new UpdateKingdom($user, $kingdomData));
    }
}
