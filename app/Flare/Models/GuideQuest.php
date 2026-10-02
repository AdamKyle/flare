<?php

namespace App\Flare\Models;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Values\GuideQuestRequiredItemSource;
use App\Game\Skills\Values\SkillTypeValue;
use Database\Factories\GuideQuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuideQuest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'intro_text',
        'instructions',
        'desktop_instructions',
        'mobile_instructions',
        'required_level',
        'required_reincarnation_amount',
        'required_skill',
        'required_skill_level',
        'required_secondary_skill',
        'required_secondary_skill_level',
        'required_faction_id',
        'required_faction_level',
        'required_game_map_id',
        'required_quest_id',
        'required_quest_item_id',
        'secondary_quest_item_id',
        'required_kingdoms',
        'required_kingdom_level',
        'required_kingdom_units',
        'required_kingdom_building_id',
        'required_kingdom_building_level',
        'required_passive_skill',
        'required_passive_level',
        'required_skill_type',
        'required_skill_type_level',
        'required_class_specials_equipped',
        'required_class_rank_level',
        'required_gold',
        'required_gold_dust',
        'required_shards',
        'required_copper_coins',
        'required_gold_bars',
        'required_stats',
        'required_str',
        'required_dex',
        'required_int',
        'required_dur',
        'required_chr',
        'required_agi',
        'required_focus',
        'faction_points_per_kill',
        'gold_dust_reward',
        'shards_reward',
        'gold_reward',
        'xp_reward',
        'parent_id',
        'unlock_at_level',
        'only_during_event',
        'be_on_game_map',
        'required_event_goal_participation',
        'required_event_goal_crafting_participation',
        'required_event_goal_enchanting_participation',
        'required_holy_stacks',
        'required_attached_gems',
        'required_specialty_type',
        'must_be_pledged_to_faction',
        'must_be_assisting_npc',
        'required_fame_level',
        'required_delve_survival_time',
        'required_delve_pack_size',
        'required_batch_crafting_type',
        'required_batch_crafting_hours',
        'required_batch_crafted_items',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'intro_text' => 'array',
        'desktop_instructions' => 'array',
        'mobile_instructions' => 'array',
        'required_level' => 'integer',
        'required_reincarnation_amount' => 'integer',
        'required_skill' => 'integer',
        'required_skill_level' => 'integer',
        'required_required_skill' => 'integer',
        'required_required_skill_level' => 'integer',
        'required_faction_id' => 'integer',
        'required_faction_level' => 'integer',
        'required_game_map_id' => 'integer',
        'required_quest_id' => 'integer',
        'required_quest_item_id' => 'integer',
        'secondary_quest_item_id' => 'integer',
        'required_kingdoms' => 'integer',
        'required_kingdom_level' => 'integer',
        'required_kingdom_units' => 'integer',
        'required_kingdom_building_id' => 'integer',
        'required_kingdom_building_level' => 'integer',
        'required_passive_skill' => 'integer',
        'required_passive_level' => 'integer',
        'required_skill_type' => 'integer',
        'required_skill_type_level' => 'integer',
        'required_class_specials_equipped' => 'integer',
        'required_class_rank_level' => 'integer',
        'required_stats' => 'integer',
        'required_str' => 'integer',
        'required_dex' => 'integer',
        'required_int' => 'integer',
        'required_dur' => 'integer',
        'required_chr' => 'integer',
        'required_agi' => 'integer',
        'required_focus' => 'integer',
        'required_gold' => 'integer',
        'required_gold_dust' => 'integer',
        'required_shards' => 'integer',
        'required_gold_bars' => 'integer',
        'required_copper_coins' => 'integer',
        'gold_reward' => 'integer',
        'gold_dust_reward' => 'integer',
        'shards_reward' => 'integer',
        'faction_points_per_kill' => 'integer',
        'xp_reward' => 'integer',
        'parent_id' => 'integer',
        'unlock_at_level' => 'integer',
        'only_during_event' => 'integer',
        'be_on_game_map' => 'integer',
        'required_event_goal_participation' => 'integer',
        'required_event_goal_crafting_participation' => 'integer',
        'required_event_goal_enchanting_participation' => 'integer',
        'required_holy_stacks' => 'integer',
        'required_attached_gems' => 'integer',
        'required_fame_level' => 'integer',
        'required_delve_survival_time' => 'integer',
        'required_delve_pack_size' => 'integer',
        'required_batch_crafting_type' => 'string',
        'required_batch_crafting_hours' => 'integer',
        'required_batch_crafted_items' => 'array',
    ];

    protected $appends = [
        'skill_name',
        'faction_name',
        'game_map_name',
        'quest_name',
        'quest_item_name',
        'secondary_quest_item_name',
        'passive_name',
        'secondary_skill_name',
        'skill_type_name',
        'kingdom_building_name',
        'parent_quest_name',
        'required_to_be_on_game_map_name',
        'required_batch_crafting_type_name',
        'required_batch_crafted_item_names',
        'has_walkthrough',
    ];

    /**
     * Return the ordered walkthrough steps owned by this Guide Quest.
     *
     * @return HasMany
     */
    public function walkthroughSteps(): HasMany
    {
        return $this->hasMany(GuideQuestWalkthroughStep::class)->orderBy('position');
    }

    /**
     * Determine whether this Guide Quest has an authored walkthrough.
     *
     * @return bool
     */
    public function getHasWalkthroughAttribute(): bool
    {
        return $this->walkthroughSteps()->exists();
    }

    /**
     * Return the name of the required primary Skill.
     *
     * @return string|null
     */
    public function getSkillNameAttribute(): ?string
    {
        return GameSkill::find($this->required_skill)?->name;
    }

    /**
     * Return the name of the required Skill type.
     *
     * @return string|null
     */
    public function getSkillTypeNameAttribute(): ?string
    {
        if (is_null($this->required_skill_type)) {
            return null;
        }

        return SkillTypeValue::getValues()[$this->required_skill_type];
    }

    /**
     * Return the name of the required secondary Skill.
     *
     * @return string|null
     */
    public function getSecondarySkillNameAttribute(): ?string
    {
        return GameSkill::find($this->required_secondary_skill)?->name;
    }

    /**
     * Return the name of the required Quest.
     *
     * @return string|null
     */
    public function getQuestNameAttribute(): ?string
    {
        return Quest::find($this->required_quest_id)?->name;
    }

    /**
     * Return the name of the required Passive Skill.
     *
     * @return string|null
     */
    public function getPassiveNameAttribute(): ?string
    {
        return PassiveSkill::find($this->required_passive_skill)?->name;
    }

    /**
     * Return the display name of the required quest Item.
     *
     * @return string|null
     */
    public function getQuestItemNameAttribute(): ?string
    {
        return Item::where('type', 'quest')->where('id', $this->required_quest_item_id)->first()?->affix_name;
    }

    /**
     * Return the display name of the secondary required quest Item.
     *
     * @return string|null
     */
    public function getSecondaryQuestItemNameAttribute(): ?string
    {
        return Item::where('type', 'quest')->where('id', $this->secondary_quest_item_id)->first()?->affix_name;
    }

    /**
     * Return the name of the Game Map whose Faction the Guide Quest requires.
     *
     * @return string|null
     */
    public function getFactionNameAttribute(): ?string
    {
        return GameMap::find($this->required_faction_id)?->name;
    }

    /**
     * Return the name of the Game Map the Guide Quest requires access to.
     *
     * @return string|null
     */
    public function getGameMapNameAttribute(): ?string
    {
        return GameMap::find($this->required_game_map_id)?->name;
    }

    /**
     * Return the name of the required Kingdom building.
     *
     * @return string|null
     */
    public function getKingdomBuildingNameAttribute(): ?string
    {
        return GameBuilding::find($this->required_kingdom_building_id)?->name;
    }

    /**
     * Return the name of the parent Guide Quest.
     *
     * @return string|null
     */
    public function getParentQuestNameAttribute(): ?string
    {
        return GuideQuest::find($this->parent_id)?->name;
    }

    /**
     * Return the name of the Game Map the Character must be on.
     *
     * @return string|null
     */
    public function getRequiredToBeOnGameMapNameAttribute(): ?string
    {
        if (is_null($this->be_on_game_map)) {
            return null;
        }

        return GameMap::find($this->be_on_game_map)->name;
    }

    /**
     * Return the display name of the required Batch Crafting experience type.
     *
     * @return string|null
     */
    public function getRequiredBatchCraftingTypeNameAttribute(): ?string
    {
        return match (BatchCraftingType::tryFrom($this->required_batch_crafting_type ?? '')) {
            BatchCraftingType::CRAFT => 'Craft For Experience',
            BatchCraftingType::CRAFT_AND_ENCHANT => 'Craft and Enchant For Experience',
            BatchCraftingType::ALCHEMY => 'Alchemy For Experience',
            BatchCraftingType::TRINKETRY => 'Trinketry For Experience',
            default => null,
        };
    }

    /**
     * Return the display details of each required batch-crafted or Alchemy Bag item.
     *
     * @return array
     */
    public function getRequiredBatchCraftedItemNamesAttribute(): array
    {
        if (empty($this->required_batch_crafted_items)) {
            return [];
        }

        return collect($this->required_batch_crafted_items)
            ->map(fn (array $requirement, int $requirementIndex): ?array => $this->requiredBatchCraftedItemDetails($requirement, $requirementIndex))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Build the display details of one required item, or null when the Item no longer exists.
     *
     * @param array $requirement
     * @param int $requirementIndex
     * @return array|null
     */
    private function requiredBatchCraftedItemDetails(array $requirement, int $requirementIndex): ?array
    {
        $item = Item::find($requirement['item_id'] ?? null);

        if (is_null($item)) {
            return null;
        }

        $source = GuideQuestRequiredItemSource::tryFrom($requirement['source'] ?? '') ?? GuideQuestRequiredItemSource::INVENTORY;
        $isAlchemyBagItem = $source === GuideQuestRequiredItemSource::ALCHEMY_BAG;

        return [
            'requirement_index' => $requirementIndex,
            'source' => $requirement['source'] ?? GuideQuestRequiredItemSource::INVENTORY->value,
            'item_id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'type_name' => $isAlchemyBagItem
                ? $this->alchemyItemTypeName($item->alchemy_type)
                : $this->batchCraftedItemTypeName($item->type),
            'amount' => $requirement['amount'] ?? 0,
            'must_be_enchanted' => ! $isAlchemyBagItem && ($requirement['must_be_enchanted'] ?? false),
        ];
    }

    /**
     * Return the display name of a craftable Item type.
     *
     * @param string $type
     * @return string
     */
    private function batchCraftedItemTypeName(string $type): string
    {
        return match ($type) {
            ItemType::DAGGER->value => 'Daggers',
            ItemType::SPELL_DAMAGE->value => 'Spell Damage',
            ItemType::SPELL_HEALING->value => 'Spell Healing',
            default => ItemType::getProperNameForType($type),
        };
    }

    /**
     * Return the display name of an Alchemy Item type.
     *
     * @param string|null $alchemyType
     * @return string
     */
    private function alchemyItemTypeName(?string $alchemyType): string
    {
        return match ($alchemyType) {
            AlchemyItemType::INCREASE_STATS->value => 'Increases Stats',
            AlchemyItemType::INCREASE_SKILL_TYPE->value => 'Increases Training Skills',
            AlchemyItemType::INCREASE_DAMAGE->value => 'Increases Damage',
            AlchemyItemType::INCREASE_ARMOUR->value => 'Increases Armour',
            AlchemyItemType::INCREASE_HEALING->value => 'Increases Healing',
            AlchemyItemType::INCREASE_ALCHEMY_SKILL->value => 'Increases Alchemy Skill',
            AlchemyItemType::DAMAGES_KINGDOMS->value => 'Damages Kingdoms',
            AlchemyItemType::HOLY_OILS->value => 'Holy Oils',
            default => ItemType::getProperNameForType($alchemyType ?? 'alchemy'),
        };
    }

    /**
     * Return the event this Guide Quest is only available during, when it has one.
     *
     * @return EventType|null
     */
    public function eventType(): ?EventType
    {
        if (is_null($this->only_during_event)) {
            return null;
        }

        return new EventType($this->only_during_event);
    }

    /**
     * Return the factory used to create Guide Quests.
     *
     * @return GuideQuestFactory
     */
    protected static function newFactory(): GuideQuestFactory
    {
        return GuideQuestFactory::new();
    }
}
