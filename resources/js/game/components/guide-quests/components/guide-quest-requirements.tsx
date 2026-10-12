import React, { ReactNode } from 'react';

import GuideQuestRequirementsProps from './types/guide-quest-requirements-props';

import {
  formatNumberWithCommas,
  formatPercent,
} from 'game-utils/format-number';

const GuideQuestRequirements = ({
  quest,
  completed_requirements: completedRequirements,
  batch_crafted_item_requirements: batchCraftedItemRequirements,
}: GuideQuestRequirementsProps): ReactNode => {
  const requirements: ReactNode[] = [];
  const renderRequirement = (label: string, requirementKey: string) => (
    <li key={requirementKey}>
      {label}:{' '}
      {completedRequirements.includes(requirementKey)
        ? 'Complete'
        : 'In progress'}
    </li>
  );
  const addRequirement = (
    configured: boolean,
    label: string,
    requirementKey: string
  ): void => {
    if (configured) {
      requirements.push(renderRequirement(label, requirementKey));
    }
  };

  addRequirement(
    quest.required_level !== null,
    `Reach level ${quest.required_level}`,
    'required_level'
  );
  addRequirement(
    quest.required_reincarnation_amount !== null,
    `Reincarnate ${quest.required_reincarnation_amount} time(s)`,
    'required_reincarnation_amount'
  );
  addRequirement(
    quest.required_fame_level !== null,
    `Reach Faction Loyalty NPC level ${quest.required_fame_level}`,
    'required_fame_level'
  );
  addRequirement(
    quest.required_specialty_type !== null,
    `Own a ${quest.required_specialty_type} specialty item`,
    'required_specialty_type'
  );
  addRequirement(
    quest.required_holy_stacks !== null,
    `Reach ${quest.required_holy_stacks} Holy Stacks`,
    'required_holy_stacks'
  );
  addRequirement(
    quest.required_class_specials_equipped !== null,
    `Equip ${quest.required_class_specials_equipped} Class Rank(s)`,
    'required_class_specials_equipped'
  );
  addRequirement(
    quest.required_class_rank_level !== null,
    `Reach Class Rank level ${quest.required_class_rank_level}`,
    'required_class_rank_level'
  );
  addRequirement(
    quest.required_stats !== null,
    `All modified stats at least ${quest.required_stats}`,
    'required_stats'
  );

  const individualStats = [
    ['required_str', 'STR', quest.required_str],
    ['required_dex', 'DEX', quest.required_dex],
    ['required_dur', 'DUR', quest.required_dur],
    ['required_int', 'INT', quest.required_int],
    ['required_chr', 'CHR', quest.required_chr],
    ['required_agi', 'AGI', quest.required_agi],
    ['required_focus', 'FOCUS', quest.required_focus],
  ] as const;

  individualStats.forEach(([key, label, value]) => {
    addRequirement(value !== null, `Modified ${label} at least ${value}`, key);
  });

  const currencies = [
    ['required_gold', 'Gold', quest.required_gold],
    ['required_gold_dust', 'Gold Dust', quest.required_gold_dust],
    ['required_shards', 'Shards', quest.required_shards],
    ['required_copper_coins', 'Copper Coins', quest.required_copper_coins],
  ] as const;

  currencies.forEach(([key, label, value]) => {
    addRequirement(
      value !== null,
      `Have ${formatNumberWithCommas(value ?? 0)} ${label}`,
      key
    );
  });

  addRequirement(
    quest.required_training_skill !== null &&
      quest.required_training_xp_sacrifice !== null,
    `${quest.training_skill_name ?? 'Required Skill'} training at ${formatPercent(
      quest.required_training_xp_sacrifice ?? 0,
      5,
      0
    )}`,
    'required_training_xp_sacrifice'
  );
  addRequirement(
    quest.required_skill !== null && quest.required_skill_level !== null,
    `${quest.skill_name ?? 'Required Skill'} level ${quest.required_skill_level}`,
    'required_skill_level'
  );
  addRequirement(
    quest.required_secondary_skill !== null &&
      quest.required_secondary_skill_level !== null,
    `${quest.secondary_skill_name ?? 'Required Secondary Skill'} level ${quest.required_secondary_skill_level}`,
    'required_secondary_skill_level'
  );
  addRequirement(
    quest.required_skill_type !== null &&
      quest.required_skill_type_level !== null,
    `${quest.skill_type_name ?? 'Required Skill Type'} level ${quest.required_skill_type_level}`,
    'required_skill_type_level'
  );
  addRequirement(
    quest.required_faction_id !== null && quest.required_faction_level !== null,
    `${quest.faction_name ?? 'Required Faction'} level ${quest.required_faction_level}`,
    'required_faction_level'
  );
  addRequirement(
    quest.required_exploration_hours !== null,
    `Complete an Exploration lasting at least ${quest.required_exploration_hours} hour(s)`,
    'required_exploration_hours'
  );
  addRequirement(
    quest.required_delve_survival_time !== null,
    `Complete a Delve lasting at least ${quest.required_delve_survival_time} hour(s)`,
    'required_delve_survival_time'
  );
  addRequirement(
    quest.required_delve_pack_size !== null,
    `Reach Delve pack size ${quest.required_delve_pack_size}`,
    'required_delve_pack_size'
  );
  addRequirement(
    quest.required_game_map_id !== null,
    `Unlock access to ${quest.game_map_name ?? 'the required map'}`,
    'required_game_map_id'
  );
  addRequirement(
    quest.required_quest_id !== null,
    `Complete ${quest.quest_name ?? 'the required quest'}`,
    'required_quest_id'
  );
  addRequirement(
    quest.required_quest_item_id !== null,
    `Obtain ${quest.quest_item_name ?? 'the required Quest Item'}`,
    'required_quest_item_id'
  );
  addRequirement(
    quest.secondary_quest_item_id !== null,
    `Obtain ${quest.secondary_quest_item_name ?? 'the secondary required Quest Item'}`,
    'secondary_quest_item_id'
  );
  addRequirement(
    quest.be_on_game_map !== null,
    `Be on ${quest.required_to_be_on_game_map_name ?? 'the required map'}`,
    'required_to_be_on_game_map_name'
  );
  addRequirement(
    quest.required_kingdoms !== null,
    `Own ${quest.required_kingdoms} Kingdom(s)`,
    'required_kingdoms'
  );
  addRequirement(
    quest.required_kingdom_level !== null,
    `Have a Kingdom with total building level ${quest.required_kingdom_level}`,
    'required_kingdom_level'
  );
  addRequirement(
    quest.required_gold_bars !== null,
    `Have a Kingdom with ${formatNumberWithCommas(quest.required_gold_bars ?? 0)} Gold Bars`,
    'required_gold_bars'
  );
  addRequirement(
    quest.required_kingdom_building_id !== null &&
      quest.required_kingdom_building_level !== null,
    `${quest.kingdom_building_name ?? 'Required Kingdom Building'} level ${quest.required_kingdom_building_level}`,
    'required_kingdom_building_level'
  );
  addRequirement(
    quest.required_kingdom_units !== null,
    `Own ${formatNumberWithCommas(quest.required_kingdom_units ?? 0)} Kingdom units`,
    'required_kingdom_units'
  );
  addRequirement(
    quest.required_passive_skill !== null &&
      quest.required_passive_level !== null,
    `${quest.passive_name ?? 'Required Passive Skill'} level ${quest.required_passive_level}`,
    'required_passive_level'
  );
  addRequirement(
    quest.required_batch_crafting_type !== null &&
      quest.required_batch_crafting_hours !== null,
    `${quest.required_batch_crafting_type_name ?? 'Batch Crafting'} for experience for at least ${quest.required_batch_crafting_hours} hour(s)`,
    'required_batch_crafting_hours'
  );

  quest.required_batch_crafted_item_names.forEach((item) => {
    const progress = batchCraftedItemRequirements.find(
      (requirement) => requirement.requirement_index === item.requirement_index
    );
    const requiredAmount = progress?.required_amount ?? item.amount;
    const currentAmount = progress?.current_amount ?? 0;
    const enchanted = item.must_be_enchanted ? 'enchanted ' : '';
    const location = item.source === 'alchemy_bag' ? ' in the Alchemy Bag' : '';

    requirements.push(
      <li key={`required_batch_crafted_items-${item.requirement_index}`}>
        Have {requiredAmount} {enchanted}
        {item.name}
        {location} ({currentAmount}/{requiredAmount}):{' '}
        {progress?.is_complete ? 'Complete' : 'In progress'}
      </li>
    );
  });

  addRequirement(
    quest.required_event_goal_participation !== null,
    `Get ${quest.required_event_goal_participation} Global Event kills`,
    'required_event_goal_participation'
  );
  addRequirement(
    quest.required_event_goal_crafting_participation !== null,
    `Craft ${quest.required_event_goal_crafting_participation} items for the Global Event`,
    'required_event_goal_crafting_participation'
  );
  addRequirement(
    quest.required_event_goal_enchanting_participation !== null,
    `Enchant ${quest.required_event_goal_enchanting_participation} items for the Global Event`,
    'required_event_goal_enchanting_participation'
  );

  if (requirements.length === 0) {
    requirements.push(
      <li key="no-requirements">No additional requirements.</li>
    );
  }

  return (
    <section aria-labelledby="guide-quest-requirements">
      <h4
        id="guide-quest-requirements"
        className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold"
      >
        Requirements
      </h4>
      <ul className="list-disc pl-5">{requirements}</ul>
    </section>
  );
};

export default GuideQuestRequirements;
