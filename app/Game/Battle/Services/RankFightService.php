<?php

namespace App\Game\Battle\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\RankFight;
use App\Flare\Models\RankFightTop;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Flare\Models\Character;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Core\Traits\HandleCharacterLevelUp;

class RankFightService {

    use ResponseBuilder, HandleCharacterLevelUp;

    /**
     * @var BattleEventHandler $battleEventHandler
     */
    private BattleEventHandler $battleEventHandler;

    /**
     * @var CharacterCacheData $characterCacheData
     */
    private CharacterCacheData $characterCacheData;

    /**
     * @var MonsterPlayerFight|null $monsterPlayerFight
     */
    private ?MonsterPlayerFight $monsterPlayerFight;

    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @var BuildMythicItem $buildMythicItem
     */
    private BuildMythicItem $buildMythicItem;

    /**
     * @param BattleEventHandler $battleEventHandler
     * @param CharacterCacheData $characterCacheData
     * @param MonsterPlayerFight $monsterPlayerFight
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param BuildMythicItem $buildMythicItem
     */
    public function __construct(BattleEventHandler $battleEventHandler,
                                CharacterCacheData $characterCacheData,
                                MonsterPlayerFight $monsterPlayerFight,
                                RandomAffixGenerator $randomAffixGenerator,
                                BuildMythicItem $buildMythicItem,
    ) {
        $this->battleEventHandler   = $battleEventHandler;
        $this->characterCacheData   = $characterCacheData;
        $this->monsterPlayerFight   = $monsterPlayerFight;
        $this->randomAffixGenerator = $randomAffixGenerator;
        $this->buildMythicItem      = $buildMythicItem;
    }

    /**
     * Set up the rank fight.
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $rank
     * @return array
     * @throws Exception
     */
    public function setupFight(Character $character, Monster $monster, int $rank): array {

        $this->characterCacheData->characterSheetCache($character, true);

        $monsterPlayerFight = $this->monsterPlayerFight->setUpRankFight($character, $monster->id, $rank);

        if (is_array($monsterPlayerFight)) {
            return $this->errorResult($monsterPlayerFight['message']);
        }

        $data     = $monsterPlayerFight->fightSetUp($rank, true);
        $health   = $data['health'];

        $messages = $monsterPlayerFight->getBattleMessages();
        $messages = [...$data['opening_messages'], ...$messages];

        if ($health['character_health'] <= 0) {
            $health['character_health'] = 0;

            $messages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type'    => 'enemy-action',
            ];

            $this->battleEventHandler->processDeadCharacter($character);

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            return $this->successResult([
                'health'     => $health,
                'messages'   => $messages,
                'is_dead'    => true,
                'monster_id' => $this->monsterPlayerFight->getMonster()['id'],
            ]);
        }

        if ($health['monster_health'] <= 0) {
            $health['monster_health'] = 0;

            $messages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type'    => 'enemy-action',
            ];

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            $this->handleRankFightMonsterDeath($character, $monsterPlayerFight->getMonster(), $data['rank']);

            return $this->successResult([
                'health'     => $health,
                'messages'   => $messages,
                'is_dead'    => false,
                'monster_id' => $this->monsterPlayerFight->getMonster()['id'],
            ]);
        }

        return $this->successResult([
            'health'     => $health,
            'messages'   => $messages,
            'is_dead'    => false,
            'monster_id' => $this->monsterPlayerFight->getMonster()['id'],
        ]);
    }

    /**
     * Fight the monster.
     *
     * @param Character $character
     * @param string $attackType
     * @return array
     * @throws Exception
     */
    public function fight(Character $character, string $attackType): array {
        $result = $this->monsterPlayerFight->setCharacter($character)->fightMonster(true, true, $attackType);

        if ($result) {

            $messages = $this->monsterPlayerFight->getBattleMessages();

            $characterHealth = $this->monsterPlayerFight->getCharacterHealth();

            $this->handleRankFightMonsterDeath($character, $this->monsterPlayerFight->getMonster(), $this->monsterPlayerFight->getRank());

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            return $this->successResult([
                'messages' => $messages,
                'health'   => [
                    'character_health' => $characterHealth,
                    'monster_health'   => 0,
                ],
                'is_dead'  => false,
            ]);
        }

        $characterHealth = $this->monsterPlayerFight->getCharacterHealth();
        $characterHealth = max($characterHealth, 0);
        $monsterHealth   = $this->monsterPlayerFight->getMonsterHealth();

        if ($characterHealth === 0) {
            $this->battleEventHandler->processDeadCharacter($character);

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            return $this->successResult([
                'health'   => [
                    'character_health' => 0,
                    'monster_health'   => $monsterHealth,
                ],
                'messages' => $this->monsterPlayerFight->getBattleMessages(),
                'is_dead'  => true,
            ]);
        }

        $this->updateAttackCache($character);

        return $this->successResult([
            'messages' => $this->monsterPlayerFight->getBattleMessages(),
            'health'   => [
                'character_health' => $characterHealth,
                'monster_health'   => $this->monsterPlayerFight->getMonsterHealth(),
            ]
        ]);
    }

    /**
     * Update the attack cache.
     *
     * @param Character $character
     * @return void
     */
    protected function updateAttackCache(Character $character): void {
        $cacheData = Cache::get('rank-fight-for-character-' . $character->id);

        if (!is_null($cacheData)) {
            $cacheData['health']['character_health'] = $this->monsterPlayerFight->getCharacterHealth();
            $cacheData['health']['monster_health'] = $this->monsterPlayerFight->getMonsterHealth();

            Cache::put('rank-fight-for-character-' . $character->id, $cacheData);
        }
    }

    /**
     * Handle when the monster dies in a rank fight.
     *
     * @param Character $character
     * @param array $monster
     * @param int $rank
     * @return void
     * @throws Exception
     */
    protected function handleRankFightMonsterDeath(Character $character, array $monster, int $rank): void {

        BattleAttackHandler::dispatch($character->id, $monster['id'])->onQueue('default_long')->delay(now()->addSeconds(2));

        event(new AttackTimeOutEvent($character));

        $isLastMonsterForRank = $this->isLastMonsterForRank($rank, $monster['id']);

        $this->logRankFight($character, $rank, $isLastMonsterForRank);
    }

    /**
     * Is this the last monster in the list for the rank?
     *
     * @param int $rank
     * @param int $monsterId
     * @return bool
     */
    protected function isLastMonsterForRank(int $rank, int $monsterId): bool {
        $monsters = Cache::get('rank-monsters')[$rank];
        $index    = array_search($monsterId, array_column($monsters, 'id'));

        if ($index !== false) {
            return $monsters[$index]['id'] === $monsters[count($monsters) - 1]['id'];
        }

        return false;
    }

    /**
     * Log the rank fight.
     *
     * @param Character $character
     * @param int $rank
     * @param bool $isLastMonsterForRank
     * @return void
     * @throws Exception
     */
    protected function logRankFight(Character $character, int $rank, bool $isLastMonsterForRank = false) {
        $characterCurrentRank = $character->rankTop;

        if ($rank === RankFight::first()->current_rank && $isLastMonsterForRank) {

            $rankFound = RankFightTop::where('current_rank', 10)->first();

            if (is_null($rankFound)) {
                if (is_null($characterCurrentRank)) {
                    $character->rankTop()->create([
                        'character_id'          => $character->id,
                        'current_rank'          => $rank,
                        'rank_achievement_date' => now(),
                    ]);
                } else {
                    $character->rankTop()->update([
                        'current_rank' => $rank,
                        'rank_achievement_date' => now(),
                    ]);
                }

                $this->firstOneToMaxRank($character->refresh(), $rank);
            }
        }

        if ($isLastMonsterForRank) {
            $this->regularMaxRank($character, $rank, $characterCurrentRank);
        }
    }

    /**
     * Regular rank rewards.
     *
     * @param Character $character
     * @param int $rank
     * @param RankFightTop|null $characterCurrentRank
     * @return void
     * @throws Exception
     */
    protected function regularMaxRank(Character $character, int $rank, ?RankFightTop $characterCurrentRank = null): void {
        if (is_null($characterCurrentRank)) {
            $character->rankTop()->create([
                'character_id'          => $character->id,
                'current_rank'          => $rank,
                'rank_achievement_date' => now(),
            ]);

            $this->regularRankRewards($character, $rank);

            return;
        }

        if ($characterCurrentRank->current_rank < $rank) {
            $character->rankTop()->update([
                'current_rank' => $rank,
                'rank_achievement_date' => now(),
            ]);

            $this->regularRankRewards($character, $rank);
        }
    }

    /**
     * Handle regular rank rewards.
     *
     * @param Character $character
     * @param int $rank
     * @return void
     * @throws Exception
     */
    protected function regularRankRewards(Character $character, int $rank): void {
        $gold = $character->gold + 2000000000;

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'xp'   => $character->xp + 10000,
            'gold' => $gold,
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        event(new ServerMessageEvent($character->user, 'Gained: 10,000XP for reaching the end of Rank ' . $rank . ' Critter list'));
        event(new ServerMessageEvent($character->user, 'Gained: 2,000,000,000 Gold for reaching the end of Rank ' . $rank . ' Critter list'));

        $item = $this->giveCharacterRandomItem($character);

        if ($character->isInventoryFull()) {

            event(new ServerMessageEvent($character->user, 'You got no item as your inventory is full. Clear space for next time! (you never got the rank completion item, no worries, next rank or next month!)'));
        } else {

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            event(new ServerMessageEvent($character->user, 'Rewarded with (item with randomly generated affix(es) - [Legendary]): ' . $item->affix_name, $slot->id));
        }
    }

    /**
     * First person to reach the max rank.
     *
     * @param Character $character
     * @param int $rank
     * @return void
     * @throws Exception
     */
    protected function firstOneToMaxRank(Character $character, int $rank): void {
        $newGold = $character->gold + (MaxCurrenciesValue::MAX_GOLD / 2);

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'xp'   => $character->xp + 100000,
            'gold' => $newGold,
        ]);

        $character = $character->refresh();

        $this->handlePossibleLevelUp($character);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'Gained: 100,000XP for reaching the end of Rank ' . $rank . ' Critter list'));
        event(new ServerMessageEvent($character->user, 'Gained: 1,000,000,000,000 Gold for reaching the end of Rank ' . $rank . ' Critter list'));

        $mythic = $this->buildMythicItem->fetchMythicItem($character);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $mythic->id,
        ]);

        event(new ServerMessageEvent($character->user, 'Rewarded with (item with randomly generated affix(es) - [!!-MYTHIC-!!]): ' . $mythic->affix_name, $slot->id));

        event(new GlobalMessageEvent($character->name . 'Has reached Rank: ' . $rank . ' and is the first one to do so this month! Congratz! You have been rewarded with a Mythic item!'));
    }

    /**
     * Find a random item to attach the uniques to.
     *
     * @param Character $character
     * @return Item
     * @throws Exception
     */
    protected function giveCharacterRandomItem(Character $character): Item {
        $item = Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->whereNull('specialty_type')
                    ->whereNotIn('type', ['alchemy', 'quest', 'trinket'])
                    ->whereDoesntHave('appliedHolyStacks')
                    ->where('cost', '<=', 4000000000)
                    ->inRandomOrder()
                    ->first();


        $randomAffix = $this->randomAffixGenerator
                            ->setCharacter($character)
                            ->setPaidAmount(RandomAffixDetails::LEGENDARY);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }

        return $duplicateItem;
    }
}
