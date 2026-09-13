import React, { Fragment, ReactNode } from 'react';

import GemEffectRow from './gem-effect-row';
import AreaGemContextDefinition from '../api/definitions/area-gem-context-definition';
import { gemTypeLabel } from '../enums/gem-type';
import AreaGemContextProps from '../types/area-gem-context-props';

import Separator from 'ui/separator/separator';

interface EffectRow {
  field: string;
  label: string;
  value: number;
}

interface EffectGroup {
  title: string;
  rows: EffectRow[];
}

const buildCharacterGroup = (
  context: AreaGemContextDefinition
): EffectGroup => ({
  title: 'Character',
  rows: [
    {
      field: 'character_power_reduction',
      label: 'Character Power Reduction',
      value: context.character_power_reduction,
    },
    {
      field: 'character_xp_bonus',
      label: 'Character XP Bonus',
      value: context.reward_effects.character_xp_bonus,
    },
    {
      field: 'character_class_rank_xp_bonus',
      label: 'Character Class Rank XP Bonus',
      value: context.reward_effects.character_class_rank_xp_bonus,
    },
    {
      field: 'character_class_specialty_xp_gain',
      label: 'Character Class Specialty XP Gain',
      value: context.reward_effects.character_class_specialty_xp_gain,
    },
    {
      field: 'kingdom_passive_training_reduction',
      label: 'Kingdom Passive Training Reduction',
      value: context.reward_effects.kingdom_passive_training_reduction,
    },
  ].filter((row) => row.value > 0),
});

const buildCurrencyGroup = (
  context: AreaGemContextDefinition
): EffectGroup => ({
  title: 'Currency and Drops',
  rows: [
    {
      field: 'gold_gain',
      label: 'Gold Gain',
      value: context.reward_effects.gold_gain,
    },
    {
      field: 'gold_dust_gain',
      label: 'Gold Dust Gain',
      value: context.reward_effects.gold_dust_gain,
    },
    {
      field: 'shards_gain',
      label: 'Shards Gain',
      value: context.reward_effects.shards_gain,
    },
    {
      field: 'copper_coin_gain',
      label: 'Copper Coin Gain',
      value: context.reward_effects.copper_coin_gain,
    },
    {
      field: 'item_drop_chance_increase',
      label: 'Item Drop Chance Increase',
      value: context.reward_effects.item_drop_chance_increase,
    },
    {
      field: 'unique',
      label: 'Unique Item Drop Chance Increase',
      value: context.rarity_effects.unique,
    },
    {
      field: 'mythic',
      label: 'Mythic Item Drop Chance Increase',
      value: context.rarity_effects.mythic,
    },
    {
      field: 'cosmic',
      label: 'Cosmic Item Drop Chance Increase',
      value: context.rarity_effects.cosmic,
    },
  ].filter((row) => row.value > 0),
});

const buildMonsterCombatGroup = (
  context: AreaGemContextDefinition
): EffectGroup => {
  const rows: EffectRow[] = [
    {
      field: 'enemy_strength_increase',
      label: 'Enemy Strength Increase',
      value: context.monster_effects.enemy_strength_increase,
    },
    {
      field: 'enemy_healing_increase',
      label: 'Enemy Healing Increase',
      value: context.monster_effects.enemy_healing_increase,
    },
    {
      field: 'enemy_spell_evasion',
      label: 'Enemy Spell Evasion',
      value: context.monster_effects.enemy_spell_evasion,
    },
    {
      field: 'enemy_affix_resistance',
      label: 'Enemy Affix Resistance',
      value: context.monster_effects.enemy_affix_resistance,
    },
    {
      field: 'enemy_entrancing_chance',
      label: 'Enemy Entrancing Chance',
      value: context.monster_effects.enemy_entrancing_chance,
    },
    {
      field: 'enemy_devouring_light_chance',
      label: 'Enemy Devouring Light Chance',
      value: context.monster_effects.enemy_devouring_light_chance,
    },
    {
      field: 'enemy_devouring_darkness_chance',
      label: 'Enemy Devouring Darkness Chance',
      value: context.monster_effects.enemy_devouring_darkness_chance,
    },
    {
      field: 'enemy_ambush_chance',
      label: 'Enemy Ambush Chance',
      value: context.monster_effects.enemy_ambush_chance,
    },
    {
      field: 'enemy_ambush_resistance',
      label: 'Enemy Ambush Resistance',
      value: context.monster_effects.enemy_ambush_resistance,
    },
    {
      field: 'enemy_counter_chance',
      label: 'Enemy Counter Chance',
      value: context.monster_effects.enemy_counter_chance,
    },
    {
      field: 'enemy_counter_resistance',
      label: 'Enemy Counter Resistance',
      value: context.monster_effects.enemy_counter_resistance,
    },
  ].filter((row) => row.value > 0);

  const { atonement_type: atonementType, atonement_amount: atonementAmount } =
    context.monster_effects;

  if (
    atonementType !== null &&
    atonementAmount !== null &&
    atonementAmount > 0
  ) {
    rows.push({
      field: 'monster_atonement_amount',
      label: `Monster Atonement (${gemTypeLabel(atonementType)})`,
      value: atonementAmount,
    });
  }

  return { title: 'Monster Combat', rows };
};

const buildMonsterRewardsGroup = (
  context: AreaGemContextDefinition
): EffectGroup => ({
  title: 'Monster Rewards',
  rows: [
    {
      field: 'enemy_quest_item_drop_chance_increase',
      label: 'Enemy Quest Item Drop Chance Increase',
      value: context.reward_effects.enemy_quest_item_drop_chance_increase,
    },
    {
      field: 'monster_xp_increase',
      label: 'Monster XP Increase',
      value: context.reward_effects.monster_xp_increase,
    },
    {
      field: 'monster_gold_drop_increase',
      label: 'Monster Gold Drop Increase',
      value: context.reward_effects.monster_gold_drop_increase,
    },
  ].filter((row) => row.value > 0),
});

const buildCraftingGroup = (
  context: AreaGemContextDefinition
): EffectGroup => ({
  title: 'Crafting',
  rows: context.crafting_skill_bonuses.map((bonus) => ({
    field: 'crafting_skill_bonus',
    label: bonus.name,
    value: bonus.bonus,
  })),
});

const renderGroup = (group: EffectGroup, index: number): ReactNode => (
  <Fragment key={group.title}>
    {index > 0 && <Separator additional_css="my-2" />}
    <div>
      <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
        {group.title}
      </h4>
      <div className="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
        {group.rows.map((row) => (
          <GemEffectRow
            key={row.label}
            field={row.field}
            label={row.label}
            value={row.value}
          />
        ))}
      </div>
    </div>
  </Fragment>
);

/**
 * Build every non-zero, effective Area Gem effect row grouped for full
 * factual presentation. Shared by the full effect breakdown and the compact
 * effect summary so both read from one source of truth.
 */
export const buildAreaGemEffectGroups = (
  context: AreaGemContextDefinition
): EffectGroup[] =>
  [
    buildCharacterGroup(context),
    buildCurrencyGroup(context),
    buildCraftingGroup(context),
    buildMonsterCombatGroup(context),
    buildMonsterRewardsGroup(context),
  ].filter((group) => group.rows.length > 0);

/**
 * Permission-neutral factual presentation of a resolved Area Gem context's
 * effective, non-zero effects. Renders only what the backend resolved; no
 * multiplier math happens here.
 */
const AreaGemContext = ({ context }: AreaGemContextProps): ReactNode => {
  const groups = buildAreaGemEffectGroups(context);

  if (groups.length === 0) {
    return null;
  }

  return <div className="flex flex-col gap-1">{groups.map(renderGroup)}</div>;
};

export default AreaGemContext;
