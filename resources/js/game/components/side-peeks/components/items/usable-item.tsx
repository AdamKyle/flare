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

  const renderSkillDetails = (): ReactNode => {
    const miscSkillModifiers = Object.keys(
      miscSkillModifierLabels
    ) as MiscSkillModKey[];

    if (isEmpty(miscSkillModifiers)) {
      return null;
    }

    const visibleModifiers = miscSkillModifiers.filter(
      (mod) => item[mod] != null && item[mod]! > 0
    );

    if (isEmpty(visibleModifiers)) {
      return null;
    }

    return (
      <>
        <span>
          <strong>Effects</strong>: Training Skills
        </span>{' '}
        |{' '}
        {visibleModifiers.map((mod) => (
          <span key={mod}>
            <strong>{miscSkillModifierLabels[mod]}</strong>:{' '}
            {((item[mod] ?? 0) * 100).toFixed(2)}%
          </span>
        ))}{' '}
        |{' '}
      </>
    );
  };

  const renderBaseModifierDetails = (): ReactNode => {
    const baseModifiers = Object.keys(baseModifiersLabels) as BaseModKey[];

    if (isEmpty(baseModifiers)) {
      return null;
    }

    const visibleModifiers = baseModifiers.filter(
      (mod) => item[mod] != null && item[mod]! > 0
    );

    if (isEmpty(visibleModifiers)) {
      return null;
    }

    return (
      <>
        <span>
          <strong>Effects</strong>: Base Modifiers
        </span>{' '}
        |{' '}
        {visibleModifiers.map((mod) => (
          <span key={mod}>
            <strong>{baseModifiersLabels[mod]}</strong>:{' '}
            {((item[mod] ?? 0) * 100).toFixed(2)}%
          </span>
        ))}{' '}
        |{' '}
      </>
    );
  };

  const renderStatModifierDetails = (): ReactNode => {
    if (!item.stat_increase || item.stat_increase <= 0) {
      return null;
    }

    return (
      <>
        <span>
          <strong>Effects</strong>: All Stats Modifier
        </span>{' '}
        |{' '}
        <span>
          <strong>Increase by:</strong>: {(item.stat_increase * 100).toFixed(2)}
          %
        </span>{' '}
        |{' '}
      </>
    );
  };

  const renderLastsFor = (): ReactNode => {
    if (item.lasts_for <= 0) {
      return null;
    }

    return (
      <>
        <span>
          <strong>Lasts For: </strong>: {item.lasts_for} Minutes
        </span>{' '}
        |{' '}
        <span>
          <strong>Can Stack?: </strong>: {item.can_stack ? 'Yes' : 'No'}
        </span>{' '}
        |{' '}
      </>
    );
  };

  const renderHolyLevel = (): ReactNode => {
    if (item.holy_level == null || item.holy_level <= 0) {
      return null;
    }

    return (
      <>
        <span>
          <strong>Apply To Items?: </strong>: Yes
        </span>{' '}
        |{' '}
        <span>
          <strong>Holy Level: </strong>: {item.holy_level}
        </span>
      </>
    );
  };

  const renderDamagesKingdoms = (): ReactNode => {
    if (!item.damages_kingdoms) {
      return null;
    }

    return (
      <>
        <span>
          <strong>Kingdom Damage: </strong>:{' '}
          {(item.kingdom_damage * 100).toFixed(2)}%
        </span>
      </>
    );
  };

  const renderUsableDetails = (): ReactNode => {
    if (item.lasts_for <= 0) {
      return null;
    }

    return (
      <>
        {renderSkillDetails()}
        {renderBaseModifierDetails()}
        {renderStatModifierDetails()}
      </>
    );
  };

  const renderItemDetails = (): ReactNode => {
    return (
      <>
        <span>
          <strong>Type</strong>: {item.type}
        </span>{' '}
        | {renderLastsFor()}
        {renderUsableDetails()}
        {renderHolyLevel()}
        {renderDamagesKingdoms()}
      </>
    );
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
