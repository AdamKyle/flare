import clsx from 'clsx';
import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import UsableItemProps from './types/usable-item-props';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

const miscSkillModifierLabels = {
  increase_skill_bonus_by: 'Skill Bonus Increase',
  increase_skill_training_bonus_by: 'Skill Training Bonus Increase',
  fight_time_out_mod_bonus: 'Fight Timeout Modifier',
  move_time_out_mod_bonus: 'Move Timeout Modifier',
} as const;

const baseModifiersLabels = {
  base_ac_mod: 'Base AC Mod',
  base_damage_mod: 'Base Damage Mod',
  base_healing_mod: 'Base Healing Mod',
} as const;

type MiscSkillModKey = keyof typeof miscSkillModifierLabels;
type BaseModKey = keyof typeof baseModifiersLabels;

const UsableItem = ({ item }: UsableItemProps) => {
  const itemColor = backpackItemTextColors(item);

  const joinWithPipes = (parts: ReactNode[]): ReactNode[] =>
    parts
      .filter(Boolean)
      .flatMap((part, index) => (index > 0 ? [' | ', part] : [part]));

  const renderSkillDetails = (): ReactNode => {
    const miscSkillModifiers = Object.keys(
      miscSkillModifierLabels
    ) as MiscSkillModKey[];
    if (isEmpty(miscSkillModifiers)) return null;

    const visible = miscSkillModifiers.filter(
      (mod) => item[mod] != null && item[mod]! > 0
    );
    if (isEmpty(visible)) return null;

    const parts: ReactNode[] = [
      <span key="label-effects-train">
        <strong>Effects</strong>: Training Skills
      </span>,
      ...visible.map((mod) => (
        <span key={mod}>
          <strong>{miscSkillModifierLabels[mod]}</strong>:{' '}
          {((item[mod] ?? 0) * 100).toFixed(2)}%
        </span>
      )),
    ];

    return <>{joinWithPipes(parts)}</>;
  };

  const renderBaseModifierDetails = (): ReactNode => {
    const baseMods = Object.keys(baseModifiersLabels) as BaseModKey[];
    if (isEmpty(baseMods)) return null;

    const visible = baseMods.filter(
      (mod) => item[mod] != null && item[mod]! > 0
    );
    if (isEmpty(visible)) return null;

    const parts: ReactNode[] = [
      <span key="label-effects-base">
        <strong>Effects</strong>: Base Modifiers
      </span>,
      ...visible.map((mod) => (
        <span key={mod}>
          <strong>{baseModifiersLabels[mod]}</strong>:{' '}
          {((item[mod] ?? 0) * 100).toFixed(2)}%
        </span>
      )),
    ];

    return <>{joinWithPipes(parts)}</>;
  };

  const renderStatModifierDetails = (): ReactNode => {
    if (!item.stat_increase || item.stat_increase <= 0) return null;

    const parts: ReactNode[] = [
      <span key="label-effects-stats">
        <strong>Effects</strong>: All Stats Modifier
      </span>,
      <span key="inc-by">
        <strong>Increase by</strong>: {(item.stat_increase * 100).toFixed(2)}%
      </span>,
    ];

    return <>{joinWithPipes(parts)}</>;
  };

  const renderLastsFor = (): ReactNode => {
    if (!item.lasts_for) return null;

    const parts: ReactNode[] = [
      <span key="lasts-for">
        <strong>Lasts For</strong>: {item.lasts_for} Minutes
      </span>,
      <span key="can-stack">
        <strong>Can Stack?</strong>: {item.can_stack ? 'Yes' : 'No'}
      </span>,
    ];

    return <>{joinWithPipes(parts)}</>;
  };

  const renderHolyLevel = (): ReactNode => {
    if (item.holy_level == null || item.holy_level <= 0) return null;

    const parts: ReactNode[] = [
      <span key="apply-to-items">
        <strong>Apply To Items?</strong>: Yes
      </span>,
      <span key="holy-level">
        <strong>Holy Level</strong>: {item.holy_level}
      </span>,
    ];

    return <>{joinWithPipes(parts)}</>;
  };

  const renderDamagesKingdoms = (): ReactNode => {
    if (!item.damages_kingdoms || !item.kingdom_damage) return null;

    return (
      <span>
        <strong>Kingdom Damage</strong>:{' '}
        {(item.kingdom_damage * 100).toFixed(2)}%
      </span>
    );
  };

  const renderUsableDetails = (): ReactNode => {
    if (!item.lasts_for) return null;

    const parts: ReactNode[] = [
      renderSkillDetails(),
      renderBaseModifierDetails(),
      renderStatModifierDetails(),
    ].filter(Boolean) as ReactNode[];

    if (isEmpty(parts)) return null;

    return <>{joinWithPipes(parts)}</>;
  };

  const renderItemDetails = (): ReactNode => {
    const parts: ReactNode[] = [
      <span key="type">
        <strong>Type</strong>: {item.type}
      </span>,
      renderLastsFor(),
      renderUsableDetails(),
      renderHolyLevel(),
      renderDamagesKingdoms(),
    ].filter(Boolean) as ReactNode[];

    return <>{joinWithPipes(parts)}</>;
  };

  return (
    <button
      className={clsx(
        backpackBaseItemStyles(),
        backpackFocusRingStyles(item),
        backpackBorderStyles(item),
        backpackButtonBackground(item)
      )}
    >
      <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600"></i>
      <div className="text-left">
        <div className={clsx('text-lg font-semibold', itemColor)}>
          {item.name}
        </div>
        <p className={clsx('my-2', itemColor)}>{item.description}</p>
        <div className={clsx('text-sm', itemColor)}>{renderItemDetails()}</div>
      </div>
    </button>
  );
};

export default UsableItem;
