<?php

namespace App\Flare\Jobs;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class MergeDuplicateKingdomUnits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Kingdom
     */
    public $kingdom;

    /**
     * Create a new job instance.
     */
    public function __construct(Kingdom $kingdom)
    {
        $this->kingdom = $kingdom;
    }

    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer)
    {
        $gameUnits = GameUnit::all();

        foreach ($gameUnits as $gameUnit) {
            $units = $this->kingdom->units()->where('game_unit_id', $gameUnit->id)->get();

            if ($units->count() > 1) {
                $mergedAttributes = $this->merge($units, $gameUnit);

                $this->kingdom->units()->create($mergedAttributes);

                if (! is_null($this->kingdom->character_id)) {
                    $user = $this->kingdom->character->user;

                    $kingdom = new Item($this->kingdom->refresh(), $kingdomTransformer);
                    $kingdom = $manager->createData($kingdom)->toArray();

                    event(new UpdateKingdom($user, $kingdom));
                }
            }
        }
    }

    protected function merge(Collection $units, GameUnit $gameUnit): array
    {
        $unitAttributes = [];

        foreach ($units as $unit) {
            if (empty($unitAttributes)) {
                $unitAttributes = $unit->getAttributes();
            } else {
                $unitAttributes['amount'] += $unit->amount;
            }
        }

        $this->kingdom->units()->where('game_unit_id', $gameUnit->id)->delete();

        $this->kingdom = $this->kingdom->refresh();

        return $unitAttributes;
    }
}
