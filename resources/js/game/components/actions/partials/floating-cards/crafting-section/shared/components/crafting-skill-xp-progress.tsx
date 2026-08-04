import React, { ReactNode } from 'react';

import CraftingSkillXpProgressProps from './types/crafting-skill-xp-progress-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CraftingSkillXpProgress = ({
  xp,
}: CraftingSkillXpProgressProps): ReactNode => {
  const valueLabel = `${formatNumberWithCommas(xp.current_xp)}/${formatNumberWithCommas(xp.next_level_xp)}`;

  return (
    <ProgressBar
      label={`${xp.skill_name} Skill XP (LV: ${xp.level})`}
      value={xp.current_xp}
      max={xp.next_level_xp}
      variant={ProgressBarVariant.SUMMER}
      value_label={valueLabel}
    />
  );
};

export default CraftingSkillXpProgress;
