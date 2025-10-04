<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Events\UpdateSkillEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class DisenchantManyService
{
    use ResponseBuilder;

    public function __construct(
        private readonly SkillCheckService $skillCheckService,
    ) {}

    /**
     * Disenchant many items and return a summary payload.
     *
     * @param  array{ids?: array<int|string>, exclude?: array<int|string>}  $params
     * @return array{
     *   status:int,
     *   message:string,
     *   disenchanted_item: array<int, array{name:string,status:string,gold_dust:int}>
     * }
     */
    public function disenchantMany(Manager $manager, CharacterInventoryCountTransformer $characterInventoryCountTransformer, Character $character, array $params): array
    {
        $slots = $this->fetchEligibleSlots($character, $params);

        if ($slots->isEmpty()) {
            return $this->successResult([
                'message' => 'No eligible items to disenchant.',
                'disenchanted_item' => [],
            ]);
        }

        $disenchantingSkill = $this->getDisenchantingSkill($character);
        $maxGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        $interestDcThreshold = 500 - (int) (500 * 0.10);
        $runningGoldDustTotal = (int) $character->gold_dust;

        $processedSlotIds = [];
        $perItemSummaries = [];
        $totalGoldDustAwarded = 0;

        foreach ($slots as $slot) {
            $processedSlotIds[] = (int) $slot->id;

            $passed = $this->rollOutcome($disenchantingSkill);

            $awardedForThisItem = $this->awardForItem(
                $passed,
                $runningGoldDustTotal,
                $maxGoldDust,
                $disenchantingSkill,
                $interestDcThreshold
            );

            if ($passed) {
                event(new UpdateSkillEvent($disenchantingSkill));
            }

            $totalGoldDustAwarded += $awardedForThisItem;

            $perItemSummaries[] = $this->buildItemSummary($slot, $passed, $awardedForThisItem);
        }

        $this->finalize($character, $processedSlotIds, $runningGoldDustTotal, $maxGoldDust);

        $character = $character->refresh();

        $data = new Item($character, $characterInventoryCountTransformer);

        return $this->successResult([
            'message' => 'Disenchanted items for: '.number_format($totalGoldDustAwarded).' Gold Dust',
            'disenchanted_item' => $perItemSummaries,
            'inventory_count' => $data,
        ]);
    }

    /**
     * Fetch eligible inventory slots based on filters and constraints.
     *
     * @param  array{ids?: array<int|string>, exclude?: array<int|string>}  $params
     * @return EloquentCollection<int, InventorySlot>
     */
    private function fetchEligibleSlots(Character $character, array $params): EloquentCollection
    {
        $query = $this->baseSlotsQuery($character);

        $this->applyIncludeExcludeFilters($query, $params);

        return $query
            ->select(['id', 'item_id'])
            ->with(['item'])
            ->get();
    }

    /**
     * Build the base query for eligible inventory slots.
     *
     * @return Builder<InventorySlot>
     */
    private function baseSlotsQuery(Character $character): Builder
    {
        return InventorySlot::query()
            ->where('inventory_id', $character->inventory->id)
            ->where('equipped', false)
            ->whereHas('item', static function (Builder $itemQuery): void {
                $itemQuery->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket'])
                    ->where(static function (Builder $affixQuery): void {
                        $affixQuery->whereNotNull('item_prefix_id')
                            ->orWhereNotNull('item_suffix_id');
                    });
            });
    }

    /**
     * Apply to include/exclude item_id filters on the base query.
     *
     * @param  array{ids?: array<int|string>, exclude?: array<int|string>}  $params
     */
    private function applyIncludeExcludeFilters(Builder $query, array $params): void
    {
        if (isset($params['exclude'])) {
            $excludeIds = $params['exclude'];

            if (! empty($excludeIds)) {
                $query->whereNotIn('item_id', $excludeIds);
            }

            return;
        }

        if (isset($params['ids'])) {
            $includeIds = $params['ids'];

            if (! empty($includeIds)) {
                $query->whereIn('item_id', $includeIds);
            }
        }
    }

    /**
     * Determine pass/fail via character roll vs. DC check.
     */
    private function rollOutcome(Skill $skill): bool
    {
        $characterRoll = $this->skillCheckService->characterRoll($skill);
        $dcCheck = $this->skillCheckService->getDCCheck($skill);

        if ($characterRoll >= $dcCheck) {
            return true;
        }

        return false;
    }

    /**
     * Award and apply gold dust for a single item, mutating the running total (capped).
     */
    private function awardForItem(
        bool $passed,
        int &$runningGoldDustTotal,
        int $maxGoldDust,
        Skill $skill,
        int $interestDcThreshold
    ): int {
        if ($runningGoldDustTotal >= $maxGoldDust) {
            return 0;
        }

        if (! $passed) {
            $runningGoldDustTotal = $this->capAt($runningGoldDustTotal + 1, $maxGoldDust);

            return 1;
        }

        $baseGoldDust = $this->computeBaseGoldDust($skill);
        $runningGoldDustTotal = $this->capAt($runningGoldDustTotal + $baseGoldDust, $maxGoldDust);

        if ($this->passesInterest($interestDcThreshold)) {
            $runningGoldDustTotal = $this->capAt(
                $runningGoldDustTotal + (int) floor($runningGoldDustTotal * 0.05),
                $maxGoldDust
            );
        }

        return $baseGoldDust;
    }

    /**
     * Cap a value at the provided maximum.
     */
    private function capAt(int $value, int $cap): int
    {
        if ($value >= $cap) {
            return $cap;
        }

        return $value;
    }

    /**
     * Compute base gold dust for a pass, including skill bonus.
     */
    private function computeBaseGoldDust(Skill $skill): int
    {
        $baseGoldDust = rand(2, 1150);
        $baseGoldDust = (int) floor($baseGoldDust + ($baseGoldDust * (float) $skill->bonus));

        return $baseGoldDust;
    }

    /**
     * Check if the interest roll passes (to award +5%).
     * Made protected to allow Mockery stubbing in tests.
     */
    protected function passesInterest(int $interestDcThreshold): bool
    {
        $roll = rand(1, 500);

        if ($roll >= $interestDcThreshold) {
            return true;
        }

        return false;
    }

    /**
     * Build the per-item summary array for the response.
     *
     * @return array{name:string,status:string,gold_dust:int}
     */
    private function buildItemSummary(InventorySlot $slot, bool $passed, int $awarded): array
    {
        return [
            'name' => (string) $slot->item->affix_name,
            'status' => $passed ? 'passed' : 'failed',
            'gold_dust' => $awarded,
        ];
    }

    /**
     * Persist final state and clean up processed slots.
     *
     * @param  array<int,int>  $processedSlotIds
     */
    private function finalize(
        Character $character,
        array $processedSlotIds,
        int $finalGoldDustTotal,
        int $maxGoldDust
    ): void {
        if (! empty($processedSlotIds)) {
            InventorySlot::whereIn('id', $processedSlotIds)->delete();
        }

        $character->update([
            'gold_dust' => $finalGoldDustTotal > $maxGoldDust ? $maxGoldDust : $finalGoldDustTotal,
        ]);
    }

    /**
     * Resolve the character's Disenchanting skill; returns an empty Skill if missing.
     */
    private function getDisenchantingSkill(Character $character): Skill
    {
        $skill = $character->skills->first(static function ($skillCandidate) {
            return $skillCandidate->type()->isDisenchanting();
        });

        if ($skill instanceof Skill) {
            return $skill;
        }

        return new Skill();
    }
}
