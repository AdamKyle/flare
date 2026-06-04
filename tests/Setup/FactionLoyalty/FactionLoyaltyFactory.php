<?php

namespace Tests\Setup\FactionLoyalty;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MapNameValue;
use App\Flare\Items\Values\ItemType;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateFactionLoyaltyAutomation;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class FactionLoyaltyFactory
{
    use CreateCharacterAutomation,
        CreateFactionLoyalty,
        CreateFactionLoyaltyAutomation,
        CreateGameMap,
        CreateItem,
        CreateMap,
        CreateMonster,
        CreateNpc;

    private const int TASK_COUNT = 3;

    private const int UNIQUE_REWARD_ITEM_COUNT = 6;

    private Character $character;

    private ?CharacterAutomation $characterAutomation = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyAutomationLog $factionLoyaltyAutomationLog = null;

    private ?FactionLoyalty $pledgedFactionLoyalty = null;

    private ?FactionLoyaltyNpc $assistingFactionLoyaltyNpc = null;

    private int $requiredAmount = 1;

    private array $gameMaps = [];

    private array $factions = [];

    private array $factionLoyalties = [];

    private array $factionLoyaltyNpcs = [];

    private array $craftingItems = [];

    private array $trainingItems = [];

    private array $bountyMonsters = [];

    private array $trainingMonsters = [];

    private array $uniqueRewardItems = [];

    /**
     * Set up faction loyalty data for a character.
     */
    public function setUp(Character $character, int $mapCount = 1, int $npcCountPerMap = 3): FactionLoyaltyFactory
    {
        $this->character = $character;
        $this->gameMaps = [];
        $this->factions = [];
        $this->factionLoyalties = [];
        $this->factionLoyaltyNpcs = [];
        $this->craftingItems = [];
        $this->trainingItems = [];
        $this->bountyMonsters = [];
        $this->trainingMonsters = [];
        $this->uniqueRewardItems = [];

        $this->createMaps($mapCount);
        $this->createFactionLoyalties($npcCountPerMap);
        $this->pledgeToFactionLoyalty();
        $this->assistNpc();

        return $this;
    }

    /**
     * Set the required amount for generated tasks.
     */
    public function setRequiredAmount(int $requiredAmount): FactionLoyaltyFactory
    {
        $this->requiredAmount = $requiredAmount;

        return $this;
    }

    /**
     * Create faction loyalty automation records.
     */
    public function createAutomation(string $attackType = AttackTypeValue::ATTACK): FactionLoyaltyFactory
    {
        $this->characterAutomation = $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => $attackType,
        ]);

        $this->factionLoyaltyAutomation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $this->characterAutomation->id,
            'character_id' => $this->character->id,
            'faction_loyalty_npc_id' => $this->assistingFactionLoyaltyNpc->id,
            'failed_bounty_monster_id' => null,
            'failed_crafting_item_id' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->factionLoyaltyAutomationLog = $this->createFactionLoyaltyAutomationLog([
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            'fight_logs' => [],
            'crafting_logs' => [],
        ]);

        return $this;
    }

    /**
     * Pledge the character to a faction loyalty.
     */
    public function pledgeToFactionLoyalty(?FactionLoyalty $factionLoyalty = null): FactionLoyaltyFactory
    {
        $factionLoyalty = $factionLoyalty ?? $this->factionLoyalties[0];

        $this->character->factionLoyalties()->update([
            'is_pledged' => false,
        ]);

        $factionLoyalty->update([
            'is_pledged' => true,
        ]);

        $this->pledgedFactionLoyalty = $factionLoyalty->refresh();
        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Set the assisted faction loyalty NPC.
     */
    public function assistNpc(?FactionLoyaltyNpc $factionLoyaltyNpc = null): FactionLoyaltyFactory
    {
        $factionLoyaltyNpc = $factionLoyaltyNpc ?? $this->pledgedFactionLoyalty->factionLoyaltyNpcs()->first();

        $factionLoyaltyNpc->factionLoyalty->factionLoyaltyNpcs()->update([
            'currently_helping' => false,
        ]);

        $factionLoyaltyNpc->update([
            'currently_helping' => true,
        ]);

        $this->assistingFactionLoyaltyNpc = $factionLoyaltyNpc->refresh();
        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Get the character.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Get the character automation.
     */
    public function getCharacterAutomation(): ?CharacterAutomation
    {
        return $this->characterAutomation?->refresh();
    }

    /**
     * Get the faction loyalty automation.
     */
    public function getFactionLoyaltyAutomation(): ?FactionLoyaltyAutomation
    {
        return $this->factionLoyaltyAutomation?->refresh();
    }

    /**
     * Get the faction loyalty automation log.
     */
    public function getFactionLoyaltyAutomationLog(): ?FactionLoyaltyAutomationLog
    {
        return $this->factionLoyaltyAutomationLog?->refresh();
    }

    /**
     * Get the pledged faction loyalty.
     */
    public function getPledgedFactionLoyalty(): ?FactionLoyalty
    {
        return $this->pledgedFactionLoyalty?->refresh();
    }

    /**
     * Get the assisting faction loyalty NPC.
     */
    public function getAssistingFactionLoyaltyNpc(): ?FactionLoyaltyNpc
    {
        return $this->assistingFactionLoyaltyNpc?->refresh();
    }

    /**
     * Get the game maps.
     */
    public function getGameMaps(): array
    {
        return $this->gameMaps;
    }

    /**
     * Get the factions.
     */
    public function getFactions(): array
    {
        return $this->factions;
    }

    /**
     * Get the faction loyalties.
     */
    public function getFactionLoyalties(): array
    {
        return $this->factionLoyalties;
    }

    /**
     * Get the faction loyalty NPCs.
     */
    public function getFactionLoyaltyNpcs(): array
    {
        return $this->factionLoyaltyNpcs;
    }

    /**
     * Get crafting items for a faction loyalty NPC.
     */
    public function getCraftingItemsForNpc(FactionLoyaltyNpc $factionLoyaltyNpc): array
    {
        return $this->craftingItems[$factionLoyaltyNpc->id] ?? [];
    }

    /**
     * Get training items for a game map.
     */
    public function getTrainingItemsForMap(GameMap $gameMap): array
    {
        return $this->trainingItems[$gameMap->id] ?? [];
    }

    /**
     * Get bounty monsters for a faction loyalty NPC.
     */
    public function getBountyMonstersForNpc(FactionLoyaltyNpc $factionLoyaltyNpc): array
    {
        return $this->bountyMonsters[$factionLoyaltyNpc->id] ?? [];
    }

    /**
     * Get training monsters for a game map.
     */
    public function getTrainingMonstersForMap(GameMap $gameMap): array
    {
        return $this->trainingMonsters[$gameMap->id] ?? [];
    }

    /**
     * Get unique reward items for a game map.
     */
    public function getUniqueRewardItemsForMap(GameMap $gameMap): array
    {
        return $this->uniqueRewardItems[$gameMap->id] ?? [];
    }

    /**
     * Create maps for the setup.
     */
    private function createMaps(int $mapCount): void
    {
        $mapCount = max(1, $mapCount);
        $this->gameMaps[] = $this->getCharacterGameMap();

        while (count($this->gameMaps) < $mapCount) {
            $this->gameMaps[] = $this->createNextGameMap(count($this->gameMaps));
        }
    }

    /**
     * Get or create the character game map.
     */
    private function getCharacterGameMap(): GameMap
    {
        $character = $this->character->refresh();

        if (! is_null($character->map?->gameMap)) {
            return $character->map->gameMap;
        }

        $gameMap = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface',
            'default' => true,
            'can_traverse' => true,
        ]);

        $this->createMap([
            'character_id' => $character->id,
            'position_x' => 16,
            'position_y' => 16,
            'character_position_x' => 16,
            'character_position_y' => 16,
            'game_map_id' => $gameMap->id,
        ]);

        $this->character = $this->character->refresh();

        return $gameMap;
    }

    /**
     * Create the next game map.
     */
    private function createNextGameMap(int $index): GameMap
    {
        $mapNames = array_values(MapNameValue::$values);
        $mapName = $mapNames[$index] ?? MapNameValue::SURFACE;

        return $this->createGameMap([
            'name' => $mapName,
            'path' => 'faction-loyalty-map-'.$index,
            'default' => false,
            'can_traverse' => true,
        ]);
    }

    /**
     * Create faction loyalties.
     */
    private function createFactionLoyalties(int $npcCountPerMap): void
    {
        foreach ($this->gameMaps as $gameMap) {
            $faction = $this->createFaction($gameMap);
            $factionLoyalty = $this->createFactionLoyaltyForFaction($faction);

            $this->createNpcDataForFactionLoyalty($factionLoyalty, max(1, $npcCountPerMap));
        }
    }

    /**
     * Create a faction.
     */
    private function createFaction(GameMap $gameMap): Faction
    {
        $faction = $this->character
            ->factions()
            ->where('game_map_id', $gameMap->id)
            ->first();

        if (! is_null($faction)) {
            $faction->update([
                'current_level' => 5,
                'current_points' => 0,
                'points_needed' => 1000,
                'maxed' => true,
                'title' => null,
            ]);

            $faction = $faction->refresh();
            $this->factions[] = $faction;

            return $faction;
        }

        $faction = Faction::create([
            'character_id' => $this->character->id,
            'game_map_id' => $gameMap->id,
            'current_level' => 5,
            'current_points' => 0,
            'points_needed' => 1000,
            'maxed' => true,
            'title' => null,
        ]);

        $this->factions[] = $faction;

        return $faction;
    }

    /**
     * Create faction loyalty for a faction.
     */
    private function createFactionLoyaltyForFaction(Faction $faction): FactionLoyalty
    {
        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $this->character->id,
            'faction_id' => $faction->id,
            'is_pledged' => false,
        ]);

        $this->factionLoyalties[] = $factionLoyalty;

        return $factionLoyalty;
    }

    /**
     * Create NPC data for a faction loyalty.
     */
    private function createNpcDataForFactionLoyalty(FactionLoyalty $factionLoyalty, int $npcCountPerMap): void
    {
        $gameMap = $factionLoyalty->faction->gameMap;

        $this->createTrainingItemsForMap($gameMap);
        $this->createTrainingMonstersForMap($gameMap);
        $this->createUniqueRewardItemsForMap($gameMap);

        for ($npcNumber = 1; $npcNumber <= $npcCountPerMap; $npcNumber++) {
            $npc = $this->createNpcForMap($gameMap, $npcNumber);
            $factionLoyaltyNpc = $this->createFactionLoyaltyNpcForNpc($factionLoyalty, $npc);
            $tasks = $this->createTasksForNpc($factionLoyaltyNpc, $gameMap, $npcNumber);

            $factionLoyaltyNpc->update([
                'next_level_fame' => collect($tasks)->sum('required_amount'),
            ]);

            $this->createFactionLoyaltyNpcTask([
                'faction_loyalty_id' => $factionLoyalty->id,
                'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
                'fame_tasks' => $tasks,
            ]);

            $this->factionLoyaltyNpcs[] = $factionLoyaltyNpc->refresh();
        }
    }

    /**
     * Create an NPC for a map.
     */
    private function createNpcForMap(GameMap $gameMap, int $npcNumber): Npc
    {
        return $this->createNpc([
            'name' => 'Faction Loyalty NPC '.$gameMap->id.'-'.$npcNumber,
            'real_name' => 'Faction Loyalty NPC '.$gameMap->id.'-'.$npcNumber,
            'game_map_id' => $gameMap->id,
            'x_position' => 16,
            'y_position' => 16,
        ]);
    }

    /**
     * Create a faction loyalty NPC.
     */
    private function createFactionLoyaltyNpcForNpc(FactionLoyalty $factionLoyalty, Npc $npc): FactionLoyaltyNpc
    {
        return $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 0,
            'kingdom_item_defence_bonus' => 0.025,
            'currently_helping' => false,
        ]);
    }

    /**
     * Create tasks for an NPC.
     */
    private function createTasksForNpc(FactionLoyaltyNpc $factionLoyaltyNpc, GameMap $gameMap, int $npcNumber): array
    {
        $craftingTasks = $this->createCraftingTasksForNpc($factionLoyaltyNpc, $gameMap, $npcNumber);
        $bountyTasks = $this->createBountyTasksForNpc($factionLoyaltyNpc, $gameMap);

        return array_merge($bountyTasks, $craftingTasks);
    }

    /**
     * Create crafting tasks for an NPC.
     */
    private function createCraftingTasksForNpc(FactionLoyaltyNpc $factionLoyaltyNpc, GameMap $gameMap, int $npcNumber): array
    {
        $tasks = [];
        $this->craftingItems[$factionLoyaltyNpc->id] = [];
        $itemDefinitions = $this->getRotatedItemDefinitions($npcNumber, self::TASK_COUNT);

        foreach ($itemDefinitions as $itemDefinition) {
            $item = $this->createCraftingItem($gameMap, $itemDefinition);

            $this->craftingItems[$factionLoyaltyNpc->id][] = $item;

            $tasks[] = [
                'type' => $item->type,
                'item_name' => $item->affix_name,
                'item_id' => $item->id,
                'required_amount' => $this->requiredAmount,
                'current_amount' => 0,
            ];
        }

        return $tasks;
    }

    /**
     * Create bounty tasks for an NPC.
     */
    private function createBountyTasksForNpc(FactionLoyaltyNpc $factionLoyaltyNpc, GameMap $gameMap): array
    {
        $tasks = [];
        $this->bountyMonsters[$factionLoyaltyNpc->id] = [];

        for ($monsterNumber = 1; $monsterNumber <= self::TASK_COUNT; $monsterNumber++) {
            $monster = $this->createBountyMonster($gameMap, $monsterNumber);

            $this->bountyMonsters[$factionLoyaltyNpc->id][] = $monster;

            $tasks[] = [
                'type' => 'bounty',
                'monster_name' => $monster->name,
                'monster_id' => $monster->id,
                'required_amount' => $this->requiredAmount,
                'current_amount' => 0,
            ];
        }

        return $tasks;
    }

    /**
     * Create a crafting item.
     */
    private function createCraftingItem(GameMap $gameMap, array $itemDefinition): Item
    {
        return $this->createItem(array_merge([
            'name' => 'Faction Loyalty Crafting Item '.$gameMap->id.' '.$itemDefinition['type'],
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'specialty_type' => null,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'holy_level' => 0,
            'holy_stacks' => 0,
            'craft_only' => false,
        ], $itemDefinition));
    }

    /**
     * Create training crafting items for a map.
     */
    private function createTrainingItemsForMap(GameMap $gameMap): void
    {
        $this->trainingItems[$gameMap->id] = [];

        foreach ($this->getRotatedItemDefinitions($gameMap->id, count($this->getItemDefinitions())) as $itemDefinition) {
            $this->trainingItems[$gameMap->id][] = $this->createItem(array_merge([
                'name' => 'Faction Loyalty Training Crafting Item '.$gameMap->id.' '.$itemDefinition['type'],
                'cost' => 1,
                'can_craft' => true,
                'skill_level_required' => 1,
                'skill_level_trivial' => 100,
                'specialty_type' => null,
                'item_prefix_id' => null,
                'item_suffix_id' => null,
                'holy_level' => 0,
                'holy_stacks' => 0,
                'craft_only' => false,
            ], $itemDefinition));
        }
    }

    /**
     * Create bounty monster.
     */
    private function createBountyMonster(GameMap $gameMap, int $monsterNumber): Monster
    {
        return $this->createMonster([
            'name' => 'Faction Loyalty Bounty Monster '.$gameMap->id.'-'.$monsterNumber,
            'game_map_id' => $gameMap->id,
            'max_level' => $this->character->level + 10 + $monsterNumber,
            'xp' => 10,
            'str' => 1,
            'dur' => 1,
            'dex' => 1,
            'chr' => 1,
            'int' => 1,
            'agi' => 1,
            'focus' => 1,
            'ac' => 1,
            'health_range' => '1-8',
            'attack_range' => '1-6',
            'gold' => 25,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'is_celestial_entity' => false,
            'raid_special_attack_type' => null,
            'only_for_location_type' => null,
        ]);
    }

    /**
     * Create training monsters for a map.
     */
    private function createTrainingMonstersForMap(GameMap $gameMap): void
    {
        $this->trainingMonsters[$gameMap->id] = [];

        for ($monsterNumber = 1; $monsterNumber <= self::TASK_COUNT; $monsterNumber++) {
            $this->trainingMonsters[$gameMap->id][] = $this->createMonster([
                'name' => 'Faction Loyalty Training Monster '.$gameMap->id.'-'.$monsterNumber,
                'game_map_id' => $gameMap->id,
                'max_level' => $this->character->level + $monsterNumber,
                'xp' => 10,
                'str' => 1,
                'dur' => 1,
                'dex' => 1,
                'chr' => 1,
                'int' => 1,
                'agi' => 1,
                'focus' => 1,
                'ac' => 1,
                'health_range' => '1-8',
                'attack_range' => '1-6',
                'gold' => 25,
                'is_raid_monster' => false,
                'is_raid_boss' => false,
                'is_celestial_entity' => false,
                'raid_special_attack_type' => null,
                'only_for_location_type' => null,
            ]);
        }
    }

    /**
     * Create unique reward items for a map.
     */
    private function createUniqueRewardItemsForMap(GameMap $gameMap): void
    {
        $this->uniqueRewardItems[$gameMap->id] = [];

        foreach ($this->getRotatedItemDefinitions($gameMap->id, self::UNIQUE_REWARD_ITEM_COUNT) as $itemDefinition) {
            $this->uniqueRewardItems[$gameMap->id][] = $this->createItem(array_merge([
                'name' => 'Faction Loyalty Unique Pool Item '.$gameMap->id.' '.$itemDefinition['type'],
                'cost' => 1,
                'skill_level_required' => 1,
                'skill_level_trivial' => 100,
                'specialty_type' => null,
                'item_prefix_id' => null,
                'item_suffix_id' => null,
                'holy_level' => 0,
                'holy_stacks' => 0,
                'can_craft' => true,
                'craft_only' => false,
            ], $itemDefinition));
        }
    }

    /**
     * Get rotated item definitions.
     */
    private function getRotatedItemDefinitions(int $offset, int $amount): array
    {
        $itemDefinitions = $this->getItemDefinitions();
        $rotatedItemDefinitions = [];

        for ($definitionNumber = 0; $definitionNumber < $amount; $definitionNumber++) {
            $rotatedItemDefinitions[] = $itemDefinitions[($offset + $definitionNumber) % count($itemDefinitions)];
        }

        return $rotatedItemDefinitions;
    }

    /**
     * Get item definitions.
     */
    private function getItemDefinitions(): array
    {
        return [
            [
                'type' => ItemType::DAGGER->value,
                'crafting_type' => 'weapon',
                'base_damage' => 10,
                'base_ac' => 0,
                'base_healing' => 0,
            ],
            [
                'type' => ItemType::BOW->value,
                'default_position' => ItemType::BOW->value,
                'crafting_type' => 'weapon',
                'base_damage' => 10,
                'base_ac' => 0,
                'base_healing' => 0,
            ],
            [
                'type' => 'body',
                'crafting_type' => 'armour',
                'base_damage' => 0,
                'base_ac' => 10,
                'base_healing' => 0,
            ],
            [
                'type' => ItemType::SPELL_DAMAGE->value,
                'crafting_type' => 'spell',
                'base_damage' => 10,
                'base_ac' => 0,
                'base_healing' => 0,
            ],
            [
                'type' => ItemType::SPELL_HEALING->value,
                'crafting_type' => 'spell',
                'base_damage' => 0,
                'base_ac' => 0,
                'base_healing' => 10,
            ],
            [
                'type' => ItemType::RING->value,
                'crafting_type' => 'ring',
                'base_damage' => 0,
                'base_ac' => 0,
                'base_healing' => 0,
            ],
        ];
    }
}
