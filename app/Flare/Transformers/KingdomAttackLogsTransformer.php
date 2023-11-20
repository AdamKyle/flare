<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use Exception;
use League\Fractal\TransformerAbstract;

class KingdomAttackLogsTransformer extends TransformerAbstract {

    /**
     * @var int|null $characterId
     */
    private ?int $characterId = null;

    /**
     * Set the characterId.
     *
     * @param int $characterId
     * @return KingdomAttackLogsTransformer
     */
    public function setCharacterId(int $characterId): KingdomAttackLogsTransformer {
        $this->characterId = $characterId;

        return $this;
    }

    /**
     * @param KingdomLog $log
     * @return array
     * @throws Exception
     */
    public function transform(KingdomLog $log): array {
        return [
            'id'                       => $log->id,
            'character_id'             => $log->character_id,
            'is_mine'                  => $this->isMyLog($log),
            'attacking_character_name' => $this->getAttackingCharacterName($log->attacking_character_id),
            'from_kingdom_name'        => $this->getKingdomName($log->from_kingdom_id),
            'to_kingdom_name'          => $this->getKingdomName($log->to_kingdom_id),
            'to_x'                     => $this->getKingdomXPosition($log->to_kingdom_id),
            'to_y'                     => $this->getKingdomYPosition($log->to_kingdom_id),
            'from_x'                   => $this->getKingdomXPosition($log->to_kingdom_id),
            'from_y'                   => $this->getKingdomYPosition($log->to_kingdom_id),
            'status'                   => $this->getStatusName($log->status),
            'units_sent'               => !is_null($log->units_sent) ? $log->units_sent : [],
            'units_survived'           => !is_null($log->units_survived) ? $log->units_survived : [],
            'old_buildings'            => $log->old_buildings,
            'new_buildings'            => $log->new_buildings,
            'old_units'                => $log->old_units,
            'new_units'                => $log->new_units,
            'item_damage'              => $log->item_damage,
            'morale_loss'              => $log->morale_loss,
            'opened'                   => $log->opened,
            'created_at'               => $log->created_at->setTimezone(env('TIME_ZONE'))->format('Y-m-d H:m:s'),
            'took_kingdom'             => (new KingdomLogStatusValue($log->status))->tookKingdom(),
            'additional_details'       => $log->additional_details,
        ];
    }

    /**
     * Get kingdom X position.
     *
     * @param int|null $kingdomId
     * @return int|null
     */
    public function getKingdomXPosition(?int $kingdomId): ?int {
        if (is_null($kingdomId)) {
            return null;
        }

        return Kingdom::find($kingdomId)->x_position;
    }

    /**
     * Get kingdom Y position.
     *
     * @param int|null $kingdomId
     * @return int|null
     */
    public function getKingdomYPosition(?int $kingdomId): ?int {
        if (is_null($kingdomId)) {
            return null;
        }

        return Kingdom::find($kingdomId)->y_position;
    }

    /**
     * @param KingdomLog $log
     * @return bool
     */
    protected function isMyLog(KingdomLog $log): bool {

        $user = auth()->user();

        if (is_null($user)) {
            $character = Character::find($this->characterId);
        } else {
            $character = $user->character;
        }

        if (is_null($log->to_kingdom_id)) {
            return true;
        }

        $attackedKingdom = Kingdom::find($log->to_kingdom_id);


        return $attackedKingdom->character_id === $character->id;
    }

    /**
     * Get kingdom name.
     *
     * @param int|null $kingdomId
     * @return string|null
     */
    protected function getKingdomName(?int $kingdomId = null): ?string {

        if (is_null($kingdomId)) {
            return null;
        }

        return Kingdom::find($kingdomId)->name;
    }

    /**
     * Get the character name of the attacker.
     *
     * @param int|null $characterId
     * @return string|null
     */
    protected function getAttackingCharacterName(?int $characterId = null): ?string {
        if (is_null($characterId)) {
            return null;
        }

        return Character::find($characterId)->name;
    }

    /**
     * @param int $status
     * @return string
     * @throws Exception
     */
    protected function getStatusName(int $status): string {
        $logStatus = new KingdomLogStatusValue($status);

        if ($logStatus->attackedKingdom()) {
            return 'Attacked kingdom';
        }

        if ($logStatus->bombsDropped()) {
            return 'Bombs were dropped';
        }

        if ($logStatus->kingdomWasAttacked()) {
            return 'Kingdom was attacked';
        }

        if ($logStatus->lostAttack()) {
            return 'Lost the attack';
        }

        if ($logStatus->lostKingdom()) {
            return 'Kingdom was lost';
        }

        if ($logStatus->tookKingdom()) {
            return 'Kingdom was taken';
        }

        if ($logStatus->overPopulated()) {
            return 'Kingdom was overpopulated';
        }

        if ($logStatus->notWalked()) {
            return 'Kingdom has not been walked';
        }

        return 'Error. Unknown status';
    }
}
