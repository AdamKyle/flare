<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Faction;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\CharacterXPService;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterFactions;
use App\Game\Core\Jobs\AdventureItemDisenchantJob;
use App\Game\Core\Jobs\HandleAdventureRewardItems;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Values\FactionLevel;
use App\Game\Core\Values\FactionType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Events\UpdateCharacterSkills;
use App\Game\Skills\Services\DisenchantService;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item as ResourceItem;

class AdventureRewardService {

    use CanHaveQuestItem;

    /**
     * @var CharacterService $characterService
     */
    private $characterService;

    private $characterRewardService;

    private $buildCharacterAttackTypes;

    private $characterXPService;

    private $randomAffixGenerator;

    private $disenchantService;

    /**
     * @var array $messages
     */
    private $messages = [];


    /**
     * @param CharacterService $characterService
     * @return void
     */
    public function __construct(CharacterService $characterService,
                                CharacterRewardService $characterRewardService,
                                BuildCharacterAttackTypes $buildCharacterAttackTypes,
                                CharacterXPService $characterXPService,
                                InventorySetService $inventorySetService,
                                RandomAffixGenerator $randomAffixGenerator,
                                DisenchantService $disenchantService,
    ) {

        $this->characterService          = $characterService;
        $this->characterRewardService    = $characterRewardService;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
        $this->characterXPService        = $characterXPService;
        $this->inventorySetService       = $inventorySetService;
        $this->randomAffixGenerator      = $randomAffixGenerator;
        $this->disenchantService         = $disenchantService;
    }

    /**
     * Distribute the rewards
     *
     * @param array $rewards
     * @param Character $character
     * @return void
     */
    public function distributeRewards(array $rewards, Character $character, AdventureLog $adventureLog): void
    {
        $adventure = $adventureLog->adventure;

        $this->handleCurrencies($character, $rewards);

        $this->handleXp($rewards['exp'], $character);

        $this->handleSkillXP($rewards, $character);

        $this->handleFactionPoints($character, $adventure, $rewards['faction_points']);

        if (!empty($rewards['items'])) {
            $this->handleItems($rewards['items'], $character, $adventureLog->overFlowSet);
        }
    }

    /**
     * Get messages for display
     *
     * @return array
     */
    public function getMessages(): array {
        return $this->messages;
    }

    protected function handleCurrencies(Character $character, array $rewards) {
        if ($character->gold !== MaxCurrenciesValue::MAX_GOLD) {
            $maxCurrencies = new MaxCurrenciesValue($character->gold + $rewards['gold'], MaxCurrenciesValue::GOLD);

            if (!$maxCurrencies->canNotGiveCurrency()) {
                $character->gold += $rewards['gold'];
                $character->save();
            } else {
                $newAmount        = $character->gold + $rewards['gold'];
                $subtractedAmount = $newAmount - MaxCurrenciesValue::MAX_GOLD;
                $newAmount        = $newAmount - $subtractedAmount;

                $character->gold = $newAmount;
                $character->save();

                event(new ServerMessageEvent($character->user,'You now are gold capped: ' . number_format($newAmount)));
            }
        }

        event(new UpdateTopBarEvent($character->refresh()));
    }

    protected function handleFactionPoints(Character $character, Adventure $adventure, int $factionPoints) {
        $faction            = $character->factions()->where('game_map_id', $adventure->location->map->id)->first();

        $points    = $faction->current_points + $factionPoints;

        $spillOver = 0;

        if ($points > $faction->points_needed) {
            $spillOver = $points - $faction->points_needed;
            $points    = $faction->points_needed;
        }

        if ($points >= $faction->points_needed && !FactionLevel::isMaxLevel($faction->current_level)) {
            $newLevel = $faction->current_level + 1;

            $faction->update([
                'current_level'  => $newLevel,
                'current_points' => 0,
                'points_needed'  => FactionLevel::getPointsNeeded($newLevel),
                'title'          => FactionType::getTitle($newLevel),
                'maxed'          => false,
            ]);

            $faction = $faction->refresh();

            event(new ServerMessageEvent($character->user,$faction->gameMap->name . ' faction has gained a new level!'));

            event(new UpdateTopBarEvent($character));

            $this->factionReward($character, $faction, $faction->gameMap->name, FactionType::getTitle($newLevel));

            $this->updateFactions($character);
        }

        if (FactionLevel::isMaxLevel($faction->current_level) && !$faction->maxed) {
            event(new ServerMessageEvent($character->user,$faction->gameMap->name . ' faction has become maxed out!'));

            event(new UpdateTopBarEvent($character));

            event(new GlobalMessageEvent($character->name . 'Has maxed out the faction for: ' . $faction->gameMap->name . ' They are considered legendary among the people of this land.'));

            $faction->update([
                'maxed' => true,
            ]);

            $faction = $faction->refresh();

            $this->updateFactions($character);
        }

        if (!$faction->maxed) {
            $faction->update([
                'current_points' => $points,
            ]);

            $faction = $faction->refresh();

            event(new ServerMessageEvent($character->user,'Gained: ' . $factionPoints . ' Faction Points for: ' . $faction->gameMap->name));

            event(new UpdateTopBarEvent($character));

            $this->updateFactions($character);
        }

        if ($spillOver > 0 && !$faction->maxed) {
            $this->handleFactionPoints($character->refresh(), $adventure, $spillOver);
        }
    }

    protected function handleXp(int $xp, Character $character): void {
        $totalLevels = floor($xp / 100);
        $oldXP       = $character->xp;

        if ($totalLevels > 0) {

            for ($i = 1; $i <= $totalLevels; $i++) {

                $this->giveXP(100, $character);

                $character = $character->refresh();
            }

            $leftOver = $xp - $totalLevels * 100;

            $this->giveXP($oldXP + $leftOver, $character);

            return;
        }

        $this->giveXP($oldXP + $xp, $character);
    }

    protected function giveXP(int $xp, Character $character) {

        $xp = $this->characterXPService->determineXPToAward($character, $xp);

        $character->xp += $xp;
        $character->save();

        event(new ServerMessageEvent($character->user, 'Awarded XP from previous adventure'));

        if ($character->xp >= $character->xp_next) {
            $this->characterRewardService->setCharacter($character)->handleCharacterLevelUp();

            event(new ServerMessageEvent($character->user, 'Gained new level, you are now: LV ' . $character->level));
        }

        event(new UpdateTopBarEvent($character));
    }

    protected function handleSkillXP(array $rewards, Character $character): void {
        if (isset($rewards['skill'])) {
            $skill = $character->skills->filter(function($skill) use($rewards) {
                return $skill->name === $rewards['skill']['skill_name'];
            })->first();

            if (is_null($skill)) {
                return;
            }

            $xp = $rewards['skill']['exp'];

            $totalLevels = floor($xp / $skill->xp_max);
            $oldXP = $skill->xp;

            if ($totalLevels > 0) {

                for ($i = 1; $i <= $totalLevels; $i++) {
                    $this->giveSkillXP($skill->xp_max, $skill);

                    $skill = $skill->refresh();
                }

                if ($skill->xp_max < $xp) {
                    $leftOver = $xp - $skill->xp_max;
                } else {
                    $leftOver = $xp;
                }

                $this->giveSkillXP($oldXP + $leftOver, $skill);

                return;
            }

            $this->giveSkillXP($oldXP + $xp, $skill);
        }
    }

    protected function giveSkillXP(int $xp, Skill $skill) {
        $skill->update([
            'xp' => $xp
        ]);

        $skill = $skill->refresh();

        $character = $skill->character;

        event(new ServerMessageEvent($character->user, 'Awarded Skill XP from previous adventure'));

        $this->updateSkills($character);

        if ($skill->xp >= $skill->xp_max) {
            if ($skill->level < $skill->max_level) {
                $level      = $skill->level + 1;

                $skill->update([
                    'level'              => $level,
                    'xp_max'             => $skill->can_train ? rand(100, 150) : rand(100, 200),
                    'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                    'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                    'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                    'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                    'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                    'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                    'xp'                 => 0,
                ]);

                $skill = $skill->refresh();

                $this->updateSkills($character);

                if ($this->shouldUpdateCharacterAttackData($skill->baseSkill)) {
                    $this->updateCharacterAttackDataCache($character);
                }

                event(new ServerMessageEvent($character->user,'Your skill: ' . $skill->name . ' gained a level and is now level: ' . $skill->level));
            }
        }

        event(new UpdateTopBarEvent($character));
    }

    public function updateSkills(Character $character) {
        $manager           = resolve(Manager::class);
        $skillsTransformer = resolve(SkillsTransformer::class);

        $skillData         = new Collection($character->skills, $skillsTransformer);
        $skillData         = $manager->createData($skillData)->toArray();

        event(new UpdateCharacterSkills($character->user, $skillData));
    }

    protected function shouldUpdateCharacterAttackData(GameSkill $skill): bool {
        if (!is_null($skill->base_damage_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->base_healing_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->base_ac_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->fight_time_out_mod_bonus_per_level)) {
            return false;
        }

        if (!is_null($skill->move_time_out_mod_bonus_per_level)) {
            return false;
        }

        return true;
    }

    protected function updateCharacterAttackDataCache(Character $character) {
        resolve(BuildCharacterAttackTypes::class)->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), resolve(CharacterSheetBaseInfoTransformer::class));

        $characterData = resolve(Manager::class)->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    protected function handleItems(array $items, Character $character, InventorySet $set = null): void {
        $character         = $character->refresh();

        if (!is_null($set)) {
            $characterSet = $character->inventorySets()->find($set->id);
        } else {
            $characterSet = $character->inventorySets->filter(function($set) {
                return $set->slots->isEmpty();
            })->first();
        }

        if (!empty($items)) {
            $jobs = [];

            foreach ($items as $index => $item) {
                if ($index !== 0) {
                    $item = Item::find($item['id']);

                    if (!is_null($item)) {
                        $jobs[] = new HandleAdventureRewardItems($character, $item, $characterSet);
                        unset($items[$index]);
                    }
                }
            }

            $item = Item::find($items[0]['id']);

            if (!is_null($item)) {
                HandleAdventureRewardItems::withChain($jobs)->dispatch($character, $item, $characterSet);
            }

            event(new ServerMessageEvent($character->user, 'Items are currently processing and will be with you shortly. Pay attention to chat, we will respect your disenchanting and selected over flow set.'));
        }
    }

    protected function factionReward(Character $character, Faction $faction, string $mapName, ?string $title = null) {
        $character = $this->giveCharacterFactionGold($character, $faction->current_level);
        $item      = $this->giveCharacterFactionRandomItem($character);

        event(new ServerMessageEvent($character->user, 'Achieved title: ' . $title . ' of ' . $mapName));

        if ($character->isInventoryFull()) {
            event(new ServerMessageEvent($character->user, 'You got no faction item as your inventory is full. Clear space for the next time!'));
        } else {

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            $character = $character->refresh();

            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

            event(new CharacterInventoryDetailsUpdate($character->user));

            event(new ServerMessageEvent($character->user, 'Faction rewarded with (item with randomly generated affix(es)): ' . $item->affix_name));
        }
    }

    protected function giveCharacterFactionGold(Character $character, int $factionLevel) {
        $gold = FactionLevel::getGoldReward($factionLevel);

        $characterNewGold = $character->gold + $gold;

        $cannotHave = (new MaxCurrenciesValue($characterNewGold, 0))->canNotGiveCurrency();

        if ($cannotHave) {
            event(new ServerMessageEvent($character->user, 'Failed to reward the faction gold as you are, or are too close to gold cap to receive: ' . number_format($gold) . ' gold.'));

            return $character;
        }

        $character->gold += $gold;

        event(new ServerMessageEvent($character->user, 'Received faction gold reward: ' . number_format($gold) . ' gold.'));

        $character->save();

        return $character->refresh();
    }

    protected function giveCharacterFactionRandomItem(Character $character) {
        $item = ItemModel::where('cost', '<=', RandomAffixDetails::BASIC)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->inRandomOrder()
            ->first();


        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::BASIC);

        $item->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if (rand(1, 100) > 50) {
            $item->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }

        return $item;
    }

    protected function updateFactions(Character $character) {
        $character = $character->refresh();

        $factions = $character->factions->transform(function($faction) {
            $faction->map_name = $faction->gameMap->name;

            return $faction;
        });

        event(new UpdateCharacterFactions($character->user, $factions));
    }

    protected function updateCharacterBaseStats(Character $character) {
        $manager = resolve(Manager::class);
        $characterBaseInfo = resolve(CharacterSheetBaseInfoTransformer::class);


        $data = new ResourceItem($character, $characterBaseInfo);
        $data = $manager->createData($data)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $data));
    }
}
