<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\MovementService;
use phpDocumentor\Reflection\Types\Boolean;

class TakeKingdomHandler {

    use KingdomCache;

    /**
     * @var MovementService $movementService
     */
    private $movementService;

    /**
     * @var array $oldKingdom
     */
    private $oldKingdom = [];

    /**
     * TakeKingdomHandler constructor.
     *
     * @param MovementService $movementService
     */
    public function __construct(MovementService $movementService) {
        $this->movementService = $movementService;
    }

    /**
     * Take the kingdom from the player.
     *
     * Update both characters cache, the taken kingdom and the map for both players.
     *
     * @param Kingdom $defender
     * @param Character $attacker
     * @param array $survivingUnits
     * @return bool
     */
    public function takeKingdom(Kingdom $defender, Character $attacker, array $survivingUnits): bool {
        $defendingCharacter = $defender->character;

        $this->setOldKingdom($defender);

        $cache = $this->removeKingdomFromCache($defendingCharacter, $defender);

        if (!is_null($cache)) {

            event(new UpdateMapDetailsBroadcast($defendingCharacter->map, $defendingCharacter->user, $this->movementService, true));

            $defender->update([
                'character_id' => $attacker->id,
                'current_morale' => .10
            ]);

            $kingdom = $this->updateKingdomsUnits($defender->refresh(), $survivingUnits);

            $this->addKingdomToCache($attacker, $kingdom);

            $this->stopOtherAttacks($attacker);

            event(new UpdateMapDetailsBroadcast($attacker->map, $attacker->user, $this->movementService, true));

            return true;
        }

        return false;
    }

    /**
     * Gets the old kingdom.
     *
     * @return array
     */
    public function getOldKingdom(): array {
        return $this->oldKingdom;
    }

    /**
     * Sets the old kingdom
     *
     * @param Kingdom $kingdom
     */
    protected function setOldKingdom(Kingdom $kingdom) {
        $this->oldKingdom = Kingdom::where('id', $kingdom->id)
                                   ->where('character_id', $kingdom->character->id)
                                   ->first()
                                   ->load('units', 'buildings')
                                   ->toArray();

    }


    /**
     * Update the kingdom with any surviving units.
     *
     * @param Kingdom $kingdom
     * @param array $survivingUnits
     * @return Kingdom
     */
    protected function updateKingdomsUnits(Kingdom $kingdom, array $survivingUnits): Kingdom {
        foreach ($survivingUnits as $unitInfo) {
            if (!$unitInfo['settler']) {
                $unit = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

                if (!is_null($unit)) {
                    $unit->update([
                        'amount' => $unit->amount + $unitInfo['amount']
                    ]);
                } else {
                    KingdomUnit::create([
                        'kingdom_id'   => $kingdom->id,
                        'game_unit_id' => $unitInfo['unit_id'],
                        'amount'       => $unitInfo['amount'],
                    ]);
                }
            }
        }

        return $kingdom->refresh();
    }

    /**
     * Stop all attacks on the kingdom.
     *
     * @param Character $attacker
     */
    protected function stopOtherAttacks(Character $attacker) {
        $unitMovements = UnitMovementQueue::where('character_id', $attacker->id);

        foreach ($unitMovements as $unitMovement) {
            $unitMovement->delete();
        }
    }
}
