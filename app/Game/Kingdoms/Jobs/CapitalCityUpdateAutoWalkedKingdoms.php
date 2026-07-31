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
use Illuminate\Support\Facades\Log;
use Throwable;

class CapitalCityUpdateAutoWalkedKingdoms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        Kingdom|int $kingdom,
    ) {
        $this->kingdomId = $kingdom instanceof Kingdom ? $kingdom->id : $kingdom;
    }

    private readonly int $kingdomId;

    public function handle(KingdomTransformer $kingdomTransformer, Manager $manager): void
    {
        $kingdom = Kingdom::with('character.user')->find($this->kingdomId);

        if (is_null($kingdom) || is_null($kingdom->character) || is_null($kingdom->character->user)) {
            return;
        }

        $kingdomData = new Item($kingdom, $kingdomTransformer);
        $kingdomData = $manager->createData($kingdomData)->toArray();
        $user = $kingdom->character->user;

        try {
            event(new UpdateKingdom($user, $kingdomData));
        } catch (Throwable $throwable) {
            Log::warning('Capital city kingdom update broadcast failed after transformation.', [
                'kingdom_id' => $this->kingdomId,
                'character_id' => $kingdom->character_id,
                'user_id' => $user->id,
                'event_class' => UpdateKingdom::class,
                'exception' => $throwable,
            ]);
        }
    }

    public function failed(Throwable $throwable): void
    {
        $kingdom = Kingdom::with('character')->find($this->kingdomId);

        Log::error('Capital city kingdom update job failed.', [
            'kingdom_id' => $this->kingdomId,
            'character_id' => $kingdom?->character_id,
            'user_id' => $kingdom?->character?->user_id,
            'exception' => $throwable,
        ]);
    }
}
