<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Contracts\CharacterGoldBarDeposit;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Database\Eloquent\Collection;

class CharacterGoldBarDepositService implements CharacterGoldBarDeposit
{
    /**
     * @param UpdateKingdom $updateKingdom
     */
    public function __construct(
        private readonly UpdateKingdom $updateKingdom,
    ) {}

    /**
     * Deposit up to the requested Gold Bars across the Character's Kingdoms and return the amount deposited.
     *
     * @param int $characterId
     * @param int $requestedAmount
     * @return int
     */
    public function deposit(int $characterId, int $requestedAmount): int
    {
        if ($requestedAmount <= 0) {
            return 0;
        }

        $character = Character::find($characterId);

        if (is_null($character)) {
            return 0;
        }

        $kingdoms = $character->kingdoms()
            ->where('gold_bars', '<', KingdomMaxValue::MAX_GOLD_BARS)
            ->orderByDesc('gold_bars')
            ->get();

        $depositAmount = min($requestedAmount, $this->remainingCapacity($kingdoms));

        if ($depositAmount === 0) {
            return 0;
        }

        $this->distribute($kingdoms, $depositAmount);

        $this->updateKingdom->updateKingdomAllKingdoms($character);

        return $depositAmount;
    }

    /**
     * Return the total number of Gold Bars the given Kingdoms can still hold.
     *
     * @param Collection $kingdoms
     * @return int
     */
    private function remainingCapacity(Collection $kingdoms): int
    {
        return $kingdoms->sum(fn ($kingdom) => KingdomMaxValue::MAX_GOLD_BARS - $kingdom->gold_bars);
    }

    /**
     * Spread the deposit as evenly as possible, filling the Kingdoms with the least room first.
     *
     * @param Collection $kingdoms
     * @param int $depositAmount
     * @return void
     */
    private function distribute(Collection $kingdoms, int $depositAmount): void
    {
        $remainingToDeposit = $depositAmount;
        $kingdomsLeft = $kingdoms->count();

        foreach ($kingdoms as $kingdom) {
            $evenShare = intdiv($remainingToDeposit + $kingdomsLeft - 1, $kingdomsLeft);
            $share = min($evenShare, KingdomMaxValue::MAX_GOLD_BARS - $kingdom->gold_bars);

            $kingdom->update(['gold_bars' => $kingdom->gold_bars + $share]);

            $remainingToDeposit -= $share;
            $kingdomsLeft--;
        }
    }
}
