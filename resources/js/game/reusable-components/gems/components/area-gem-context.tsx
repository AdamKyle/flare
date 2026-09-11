import React, { Fragment, ReactNode } from 'react';

import AreaGemContextDefinition from '../api/definitions/area-gem-context-definition';
import { gemTypeLabel } from '../enums/gem-type';
import AreaGemContextProps from '../types/area-gem-context-props';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

interface EffectRow {
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
      label: 'Character Power Reduction',
      value: context.character_power_reduction,
    },
    {
      label: 'Character XP Bonus',
      value: context.reward_effects.character_xp_bonus,
    },
    {
      label: 'Character Class Rank XP Bonus',
      value: context.reward_effects.character_class_rank_xp_bonus,
    },
    {
      label: 'Character Class Specialty XP Gain',
      value: context.reward_effects.character_class_specialty_xp_gain,
    },
    {
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
    { label: 'Gold Gain', value: context.reward_effects.gold_gain },
    { label: 'Gold Dust Gain', value: context.reward_effects.gold_dust_gain },
    { label: 'Shards Gain', value: context.reward_effects.shards_gain },
    {
      label: 'Copper Coin Gain',
      value: context.reward_effects.copper_coin_gain,
    },
    {
      label: 'Item Drop Chance Increase',
      value: context.reward_effects.item_drop_chance_increase,
    },
    {
      label: 'Unique Item Drop Chance Increase',
      value: context.rarity_effects.unique,
    },
    {
      label: 'Mythic Item Drop Chance Increase',
      value: context.rarity_effects.mythic,
    },
    {
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
      label: 'Enemy Strength Increase',
      value: context.monster_effects.enemy_strength_increase,
    },
    {
      label: 'Enemy Healing Increase',
      value: context.monster_effects.enemy_healing_increase,
    },
    {
      label: 'Enemy Spell Evasion',
      value: context.monster_effects.enemy_spell_evasion,
    },
    {
      label: 'Enemy Affix Resistance',
      value: context.monster_effects.enemy_affix_resistance,
    },
    {
      label: 'Enemy Entrancing Chance',
      value: context.monster_effects.enemy_entrancing_chance,
    },
    {
      label: 'Enemy Devouring Light Chance',
      value: context.monster_effects.enemy_devouring_light_chance,
    },
    {
      label: 'Enemy Devouring Darkness Chance',
      value: context.monster_effects.enemy_devouring_darkness_chance,
    },
    {
      label: 'Enemy Ambush Chance',
      value: context.monster_effects.enemy_ambush_chance,
    },
    {
      label: 'Enemy Ambush Resistance',
      value: context.monster_effects.enemy_ambush_resistance,
    },
    {
      label: 'Enemy Counter Chance',
      value: context.monster_effects.enemy_counter_chance,
    },
    {
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
      label: 'Enemy Quest Item Drop Chance Increase',
      value: context.reward_effects.enemy_quest_item_drop_chance_increase,
    },
    {
      label: 'Monster XP Increase',
      value: context.reward_effects.monster_xp_increase,
    },
    {
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
      <Dl>
        {group.rows.map((row) => (
          <Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatPercent(row.value)}</Dd>
          </Fragment>
        ))}
      </Dl>
    </div>
  </Fragment>
);

/**
 * Permission-neutral factual presentation of a resolved Area Gem context's
 * effective, non-zero effects. Renders only what the backend resolved; no
 * multiplier math happens here.
 */
const AreaGemContext = ({ context }: AreaGemContextProps): ReactNode => {
  const groups = [
    buildCharacterGroup(context),
    buildCurrencyGroup(context),
    buildCraftingGroup(context),
    buildMonsterCombatGroup(context),
    buildMonsterRewardsGroup(context),
  ].filter((group) => group.rows.length > 0);

  if (groups.length === 0) {
    return null;
  }

  return <div className="flex flex-col gap-1">{groups.map(renderGroup)}</div>;
};

export default AreaGemContext;
