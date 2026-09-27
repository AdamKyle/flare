import {
  GAME_MAP_EVENT_TYPE_LABELS,
  isGameMapEventType,
} from '../../../game/reusable-components/game-map/enums/game-map-event-type';
import GuideQuestDetailDefinition, {
  GuideQuestBatchCraftedItemRequirementDefinition,
} from '../api/definitions/guide-quest-detail-definition';
import { GuideQuestBatchItemSource } from '../enums/guide-quest-batch-item-source';
import GuideQuestShowSectionDefinition, {
  GuideQuestShowRowDefinition,
} from '../types/guide-quest-show-section-definition';

const formatHours = (hours: number | null): string | null => {
  if (!hours) {
    return null;
  }

  return `${hours} ${hours === 1 ? 'hour' : 'hours'}`;
};

const formatEventName = (eventType: number | null): string | null => {
  if (eventType === null || !isGameMapEventType(eventType)) {
    return null;
  }

  return GAME_MAP_EVENT_TYPE_LABELS[eventType];
};

const formatBatchCrafting = (
  guideQuest: GuideQuestDetailDefinition
): string | null => {
  const hours = formatHours(guideQuest.required_batch_crafting_hours);

  if (!guideQuest.required_batch_crafting_type_name || !hours) {
    return null;
  }

  return `Run ${guideQuest.required_batch_crafting_type_name} for at least ${hours}.`;
};

const formatBatchCraftedItem = (
  requirement: GuideQuestBatchCraftedItemRequirementDefinition
): string => {
  const destination =
    requirement.source === GuideQuestBatchItemSource.ALCHEMY_BAG
      ? 'alchemy bag'
      : 'inventory';
  const affixes = requirement.must_be_enchanted
    ? ' with both a prefix and a suffix'
    : '';

  return `Have ${requirement.amount}x ${requirement.name} of type ${requirement.type_name} in your ${destination}${affixes}.`;
};

const buildBatchCraftedItemRows = (
  requirements: GuideQuestBatchCraftedItemRequirementDefinition[]
): GuideQuestShowRowDefinition[] => {
  if (requirements.length === 0) {
    return [];
  }

  return [
    ...requirements.map((requirement) => ({
      label: `Required Item ${requirement.requirement_index + 1}`,
      value: formatBatchCraftedItem(requirement),
    })),
    {
      label: 'Item Consumption',
      value: 'These items are consumed when the guide quest is handed in.',
    },
  ];
};

export const buildGuideQuestShowSections = (
  guideQuest: GuideQuestDetailDefinition
): GuideQuestShowSectionDefinition[] => [
  {
    title: 'Progression',
    rows: [
      { label: 'Required Level', value: guideQuest.required_level },
      {
        label: 'Required Reincarnations',
        value: guideQuest.required_reincarnation_amount,
      },
      { label: 'Unlock At Level', value: guideQuest.unlock_at_level },
      { label: 'Required Skill', value: guideQuest.skill_name },
      {
        label: 'Required Skill Level',
        value: guideQuest.required_skill_level,
      },
      {
        label: 'Required Secondary Skill',
        value: guideQuest.secondary_skill_name,
      },
      {
        label: 'Required Secondary Skill Level',
        value: guideQuest.required_secondary_skill_level,
      },
      { label: 'Required Skill Type', value: guideQuest.skill_type_name },
      {
        label: 'Required Skill Type Level',
        value: guideQuest.required_skill_type_level,
      },
      { label: 'Required Passive', value: guideQuest.passive_name },
      {
        label: 'Required Passive Level',
        value: guideQuest.required_passive_level,
      },
      {
        label: 'Required Class Specials Equipped',
        value: guideQuest.required_class_specials_equipped,
      },
      {
        label: 'Required Class Rank Level',
        value: guideQuest.required_class_rank_level,
      },
      {
        label: 'Required Specialty Type',
        value: guideQuest.required_specialty_type,
      },
      { label: 'Required Fame Level', value: guideQuest.required_fame_level },
    ],
  },
  {
    title: 'World, Quests and Factions',
    rows: [
      { label: 'Required Faction', value: guideQuest.faction_name },
      {
        label: 'Required Faction Level',
        value: guideQuest.required_faction_level,
      },
      {
        label: 'Must Be Pledged To Faction',
        value: guideQuest.must_be_pledged_to_faction,
      },
      {
        label: 'Must Be Assisting NPC',
        value: guideQuest.must_be_assisting_npc,
      },
      { label: 'Required Game Map', value: guideQuest.game_map_name },
      {
        label: 'Must Be On Game Map',
        value: guideQuest.required_to_be_on_game_map_name,
      },
      { label: 'Required Quest', value: guideQuest.quest_name },
      { label: 'Parent Guide Quest', value: guideQuest.parent_quest_name },
      { label: 'Required Quest Item', value: guideQuest.quest_item_name },
      {
        label: 'Secondary Quest Item',
        value: guideQuest.secondary_quest_item_name,
      },
      {
        label: 'Only During Event',
        value: formatEventName(guideQuest.only_during_event),
      },
      {
        label: 'Required Event Goal Participation',
        value: guideQuest.required_event_goal_participation,
      },
      {
        label: 'Required Event Goal Crafting Participation',
        value: guideQuest.required_event_goal_crafting_participation,
      },
      {
        label: 'Required Event Goal Enchanting Participation',
        value: guideQuest.required_event_goal_enchanting_participation,
      },
    ],
  },
  {
    title: 'Kingdoms',
    rows: [
      { label: 'Required Kingdoms', value: guideQuest.required_kingdoms },
      {
        label: 'Required Kingdom Level',
        value: guideQuest.required_kingdom_level,
      },
      {
        label: 'Required Kingdom Units',
        value: guideQuest.required_kingdom_units,
      },
      {
        label: 'Required Kingdom Building',
        value: guideQuest.kingdom_building_name,
      },
      {
        label: 'Required Kingdom Building Level',
        value: guideQuest.required_kingdom_building_level,
      },
      { label: 'Required Gold Bars', value: guideQuest.required_gold_bars },
    ],
  },
  {
    title: 'Stats, Currencies and Equipment',
    rows: [
      { label: 'Required Stats', value: guideQuest.required_stats },
      { label: 'Required Strength', value: guideQuest.required_str },
      { label: 'Required Dexterity', value: guideQuest.required_dex },
      { label: 'Required Intelligence', value: guideQuest.required_int },
      { label: 'Required Durability', value: guideQuest.required_dur },
      { label: 'Required Charisma', value: guideQuest.required_chr },
      { label: 'Required Agility', value: guideQuest.required_agi },
      { label: 'Required Focus', value: guideQuest.required_focus },
      { label: 'Required Gold', value: guideQuest.required_gold },
      { label: 'Required Gold Dust', value: guideQuest.required_gold_dust },
      { label: 'Required Shards', value: guideQuest.required_shards },
      {
        label: 'Required Copper Coins',
        value: guideQuest.required_copper_coins,
      },
      {
        label: 'Required Holy Stacks',
        value: guideQuest.required_holy_stacks,
      },
      {
        label: 'Required Attached Gems',
        value: guideQuest.required_attached_gems,
      },
    ],
  },
  {
    title: 'Delve and Batch Crafting',
    rows: [
      {
        label: 'Required Delve Survival Time',
        value: formatHours(guideQuest.required_delve_survival_time),
      },
      {
        label: 'Required Delve Pack Size',
        value: guideQuest.required_delve_pack_size,
      },
      {
        label: 'Required Batch Crafting',
        value: formatBatchCrafting(guideQuest),
      },
      {
        label: 'Required Batch Crafting Hours',
        value: guideQuest.required_batch_crafting_hours,
      },
      ...buildBatchCraftedItemRows(
        guideQuest.required_batch_crafted_item_names
      ),
    ],
  },
  {
    title: 'Rewards',
    rows: [
      { label: 'XP Reward', value: guideQuest.xp_reward },
      { label: 'Gold Reward', value: guideQuest.gold_reward },
      { label: 'Gold Dust Reward', value: guideQuest.gold_dust_reward },
      { label: 'Shards Reward', value: guideQuest.shards_reward },
      {
        label: 'Faction Points Per Kill',
        value: guideQuest.faction_points_per_kill,
      },
    ],
  },
];
