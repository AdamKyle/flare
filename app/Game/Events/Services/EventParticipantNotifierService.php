<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Events\UpdateEventGoalProgress;

class EventParticipantNotifierService
{
    /**
     * Construct the notifier with the event goals service dependency.
     *
     * @param  EventGoalsService  $eventGoalsService  Service used to build goal payloads.
     */
    public function __construct(private EventGoalsService $eventGoalsService) {}

    /**
     * Notify all participating characters for a given goal.
     *
     * Skips when participant count is zero, when there is no participation,
     * or when there is no map associated with the goal's event type.
     *
     * @param  GlobalEventGoal  $goal  The active global event goal.
     * @param  int  $participantsCount  Precomputed number of participants.
     */
    public function notifyForGoal(GlobalEventGoal $goal, int $participantsCount): void
    {
        if ($participantsCount <= 0) {
            return;
        }

        if (! GlobalEventParticipation::query()->exists()) {
            return;
        }

        if (! GameMap::where('only_during_event_type', $goal->event_type)->exists()) {
            return;
        }

        $seen = [];

        GlobalEventParticipation::query()
            ->whereNotNull('character_id')
            ->select(['id', 'character_id'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($goal, $participantsCount, &$seen) {
                $ids = $this->extractNewCharacterIds($rows->pluck('character_id')->all(), $seen);
                if (empty($ids)) {
                    return;
                }

                [$kills, $crafts, $enchants] = $this->loadAggregates($goal->id, $ids);

                $this->notifyCharacters($ids, $goal, $participantsCount, $kills, $crafts, $enchants);
            });
    }

    /**
     * Extract only unseen character IDs and mark them as seen.
     *
     * @param  array<int,int|null>  $ids  Raw character IDs from the chunk.
     * @param  array<int,bool>  $seen  Reference map of already processed IDs.
     * @return array<int,int> Unique, unseen character IDs.
     */
    private function extractNewCharacterIds(array $ids, array &$seen): array
    {
        $unique = [];
        foreach ($ids as $id) {
            if ($id === null) {
                continue;
            }
            $id = (int) $id;
            if (! isset($seen[$id])) {
                $seen[$id] = true;
                $unique[] = $id;
            }
        }

        return $unique;
    }

    /**
     * Load per-character aggregate totals for the specified goal and characters.
     *
     * @param  int  $goalId  The global event goal ID.
     * @param  array<int,int>  $ids  Character IDs to aggregate.
     * @return array{0: array<int,int>, 1: array<int,int>, 2: array<int,int>} Tuple of [kills, crafts, enchants] keyed by character_id.
     */
    private function loadAggregates(int $goalId, array $ids): array
    {
        $kills = GlobalEventKill::query()
            ->where('global_event_goal_id', $goalId)
            ->whereIn('character_id', $ids)
            ->selectRaw('character_id, SUM(kills) as kills')
            ->groupBy('character_id')
            ->pluck('kills', 'character_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $crafts = GlobalEventCraft::query()
            ->where('global_event_goal_id', $goalId)
            ->whereIn('character_id', $ids)
            ->selectRaw('character_id, SUM(crafts) as crafts')
            ->groupBy('character_id')
            ->pluck('crafts', 'character_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $enchants = GlobalEventEnchant::query()
            ->where('global_event_goal_id', $goalId)
            ->whereIn('character_id', $ids)
            ->selectRaw('character_id, SUM(enchants) as enchants')
            ->groupBy('character_id')
            ->pluck('enchants', 'character_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return [$kills, $crafts, $enchants];
    }

    /**
     * Dispatch update events for each character with precomputed totals.
     *
     * @param  array<int,int>  $ids  Character IDs to notify.
     * @param  GlobalEventGoal  $goal  The active global event goal.
     * @param  int  $participantsCount  Precomputed number of participants.
     * @param  array<int,int>  $kills  Map of character_id => kills.
     * @param  array<int,int>  $crafts  Map of character_id => crafts.
     * @param  array<int,int>  $enchants  Map of character_id => enchants.
     */
    private function notifyCharacters(
        array $ids,
        GlobalEventGoal $goal,
        int $participantsCount,
        array $kills,
        array $crafts,
        array $enchants
    ): void {
        Character::whereIn('id', $ids)
            ->get(['id'])
            ->each(function ($character) use ($goal, $participantsCount, $kills, $crafts, $enchants) {
                $cid = $character->id;

                $payload = $this->eventGoalsService->getEventGoalDataFromNumbers(
                    $character,
                    $goal,
                    $participantsCount,
                    $kills[$cid] ?? 0,
                    $crafts[$cid] ?? 0,
                    $enchants[$cid] ?? 0
                );

                event(new UpdateEventGoalProgress($payload));
                event(new UpdateCharacterStatus($character));
            });
    }
}
