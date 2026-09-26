import clsx from 'clsx';
import React, { ReactNode, useId } from 'react';

import SkillContributingItemCardProps from './types/skill-contributing-item-card-props';
import {
  resolveItemPositionLabel,
  resolveItemTypeLabel,
} from '../../../../reusable-components/item/utils/resolve-item-labels';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from '../../partials/character-inventory/styles/backpack-item-styles';
import { SkillBonusItemSource } from '../enums/skill-bonus-item-source';
import { buildSkillItemStyleShape } from '../utils/build-skill-item-style-shape';
import { formatSkillPercentage } from '../utils/format-skill-percentage';

const SkillContributingItemCard = ({
  item,
  on_open: onOpen,
}: SkillContributingItemCardProps): ReactNode => {
  const titleId = useId();
  const detailsId = useId();

  const styleShape = buildSkillItemStyleShape(item);
  const itemColor = backpackItemTextColors(styleShape);
  const isQuestItem = item.source === SkillBonusItemSource.QUEST;

  const renderSource = (): ReactNode => {
    if (isQuestItem) {
      return (
        <span>
          <strong>Source</strong>: Quest Item
        </span>
      );
    }

    return (
      <span>
        <strong>Source</strong>: Equipped
        {item.position ? ` (${resolveItemPositionLabel(item.position)})` : ''}
      </span>
    );
  };

  const renderSkillBonus = (): ReactNode => {
    if (item.skill_bonus <= 0) {
      return null;
    }

    return (
      <span>
        <strong>Skill Bonus</strong>: {formatSkillPercentage(item.skill_bonus)}
      </span>
    );
  };

  const renderSkillXpBonus = (): ReactNode => {
    if (item.skill_training_bonus <= 0) {
      return null;
    }

    return (
      <span>
        <strong>Skill XP Bonus</strong>:{' '}
        {formatSkillPercentage(item.skill_training_bonus)}
      </span>
    );
  };

  return (
    <button
      type="button"
      className={clsx(
        backpackBaseItemStyles(),
        backpackFocusRingStyles(styleShape),
        backpackBorderStyles(styleShape),
        backpackButtonBackground(styleShape)
      )}
      onClick={() => onOpen(item)}
      aria-labelledby={titleId}
      aria-describedby={detailsId}
    >
      <i
        className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600"
        aria-hidden="true"
      />
      <span className="flex flex-col text-left">
        <span id={titleId} className={clsx('text-lg font-semibold', itemColor)}>
          {item.name}
        </span>
        <span
          id={detailsId}
          className={clsx('flex flex-col gap-1 text-sm', itemColor)}
        >
          <span>
            <strong>Type</strong>: {resolveItemTypeLabel(item.type)}
          </span>
          {renderSource()}
          {renderSkillBonus()}
          {renderSkillXpBonus()}
        </span>
      </span>
    </button>
  );
};

export default SkillContributingItemCard;
