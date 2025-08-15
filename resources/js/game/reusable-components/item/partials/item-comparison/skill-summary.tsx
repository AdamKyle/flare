import React, { Fragment, useState } from 'react';

import AdjustmentChangeDisplay from './adjustment-change-display';
import StatInfoToolTip from '../../stat-info-tool-tip';
import SkillSummaryProps from '../../types/partials/item-comparison/skill-summary-props';
import { isNilOrZeroValue } from '../../utils/item-comparison';

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

  const renderSkillRow = (id: string, label: string, value: number) => {
    const numericValue = Number(value);

    const handleOpen = () => {
      setOpenId(id);
    };

    const handleClose = () => {
      if (openId === id) {
        setOpenId(null);
      }
    };

    return (
      <Fragment key={id}>
        <Dt>
          <StatInfoToolTip
            label={label}
            value={numericValue}
            renderAsPercent
            align="left"
            size="sm"
            is_open={openId === id}
            on_open={handleOpen}
            on_close={handleClose}
          />
          <span className="min-w-0 break-words">{label}</span>
        </Dt>
        <Dd>
          <AdjustmentChangeDisplay value={numericValue} label={label} />
        </Dd>
      </Fragment>
    );
  };

  return (
    <Dl>
      {skills.map((skill, index) => {
        const elements: React.ReactNode[] = [];

        if (!isNilOrZeroValue(skill.skill_training_bonus_adjustment)) {
          elements.push(
            renderSkillRow(
              `${skill.skill_name}-training-${index}`,
              `${skill.skill_name} Training Adjustment`,
              Number(skill.skill_training_bonus_adjustment)
            )
          );
        }

        if (!isNilOrZeroValue(skill.skill_bonus_adjustment)) {
          elements.push(
            renderSkillRow(
              `${skill.skill_name}-bonus-${index}`,
              `${skill.skill_name} Bonus Adjustment`,
              Number(skill.skill_bonus_adjustment)
            )
          );
        }

        return elements;
      })}
    </Dl>
  );
};

export default SkillSummary;
