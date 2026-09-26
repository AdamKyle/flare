import React, { ReactNode, useId } from 'react';

import SkillCardProps from './types/skill-card-props';
import { skillCardStyles } from '../styles/skill-card-styles';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const SkillCard = ({ skill, on_open: onOpen }: SkillCardProps): ReactNode => {
  const xpLabelId = useId();

  const isMaxLevel = skill.level >= skill.max_level;

  const renderState = (): ReactNode => {
    if (skill.is_locked) {
      return (
        <span className="text-mango-tango-700 dark:text-mango-tango-300 flex items-center gap-1 text-xs font-medium">
          <i className="fas fa-lock" aria-hidden="true" />
          Locked
        </span>
      );
    }

    if (skill.is_training) {
      return (
        <span className="text-danube-700 dark:text-danube-300 flex items-center gap-1 text-xs font-medium">
          <i className="fas fa-crosshairs" aria-hidden="true" />
          Training
        </span>
      );
    }

    return null;
  };

  const renderXp = (): ReactNode => {
    if (isMaxLevel) {
      return (
        <p className="flex items-center gap-1 text-sm text-emerald-700 dark:text-emerald-400">
          <i className="fas fa-crown" aria-hidden="true" />
          Max level reached
        </p>
      );
    }

    return (
      <ProgressBar
        label="Skill XP"
        aria_labelledby={xpLabelId}
        value={skill.xp}
        max={skill.xp_max}
        value_label={`${skill.xp} / ${skill.xp_max}`}
        variant={ProgressBarVariant.XP}
        size={ProgressBarSize.THIN}
      />
    );
  };

  return (
    <article className={skillCardStyles}>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className="font-semibold">{skill.name}</h3>
        {renderState()}
      </div>
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Level {skill.level} / {skill.max_level} · {skill.skill_type}
      </p>
      {renderXp()}
      <Button
        label="View Skill"
        aria_label={`View ${skill.name} details`}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={() => onOpen(skill)}
      />
    </article>
  );
};

export default SkillCard;
