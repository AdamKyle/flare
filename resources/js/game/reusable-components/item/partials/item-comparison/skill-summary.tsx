import React, { Fragment, useState } from 'react';

import AdjustmentChangeDisplay from './adjustment-change-display';
import StatToolTip from '../../tool-tips/stat-tool-tip';
import type SkillSummaryProps from '../../types/partials/item-comparison/skill-summary-props';

import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const SkillSummary = ({ adjustments }: SkillSummaryProps) => {
  const [openId, setOpenId] = useState<string | null>(null);

  const skills = adjustments.skill_summary;

  if (!Array.isArray(skills) || skills.length === 0) {
    return null;
  }

  const hasAnySkillWithChange = skills.some((skill) => {
    const isTrainingZero = isNilOrZeroValue(
      skill.skill_training_bonus_adjustment
    );
    const isBonusZero = isNilOrZeroValue(skill.skill_bonus_adjustment);
    return !(isTrainingZero && isBonusZero);
  });

  if (!hasAnySkillWithChange) {
    return null;
  }

  const handleOpen = (id: string) => {
    setOpenId(id);
  };

  const handleClose = (id: string) => {
    if (openId === id) {
      setOpenId(null);
    }
  };

  const renderSkillRow = (id: string, label: string, value: number) => {
    const numericValue = Number(value);

    return (
      <Fragment key={id}>
        <Dt>
          <StatToolTip
            label={label}
            value={numericValue}
            renderAsPercent
            align="left"
            size="sm"
            is_open={openId === id}
            on_open={() => handleOpen(id)}
            on_close={() => handleClose(id)}
          />
          <span className="min-w-0 break-words">{label}</span>
        </Dt>
        <Dd>
          <AdjustmentChangeDisplay value={numericValue} label={label} />
        </Dd>
      </Fragment>
    );
  };

  const renderTrainingRow = (skill: (typeof skills)[number], index: number) => {
    if (isNilOrZeroValue(skill.skill_training_bonus_adjustment)) {
      return null;
    }

    return renderSkillRow(
      `${skill.skill_name}-training-${index}`,
      `${skill.skill_name} Training Adjustment`,
      Number(skill.skill_training_bonus_adjustment)
    );
  };

  const renderBonusRow = (skill: (typeof skills)[number], index: number) => {
    if (isNilOrZeroValue(skill.skill_bonus_adjustment)) {
      return null;
    }

    return renderSkillRow(
      `${skill.skill_name}-bonus-${index}`,
      `${skill.skill_name} Bonus Adjustment`,
      Number(skill.skill_bonus_adjustment)
    );
  };

  return (
    <Dl>
      {skills.map((skill, index) => (
        <Fragment key={`${skill.skill_name}-${index}`}>
          {renderTrainingRow(skill, index)}
          {renderBonusRow(skill, index)}
        </Fragment>
      ))}
    </Dl>
  );
};

export default SkillSummary;
