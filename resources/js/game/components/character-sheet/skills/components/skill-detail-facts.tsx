import React, { ReactNode, useId } from 'react';

import SkillDetailFactsProps from './types/skill-detail-facts-props';
import SkillFactRowDefinition from '../types/skill-fact-row-definition';
import { buildSkillModifierRows } from '../utils/build-skill-modifier-rows';
import { formatSkillPercentage } from '../utils/format-skill-percentage';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const SkillDetailFacts = ({ skill }: SkillDetailFactsProps): ReactNode => {
  const xpLabelId = useId();

  const modifierRows = buildSkillModifierRows(skill);
  const isMaxLevel = skill.level >= skill.max_level;

  const renderDescription = (): ReactNode => {
    if (!skill.description) {
      return null;
    }

    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">
        {skill.description}
      </p>
    );
  };

  const renderXp = (): ReactNode => {
    if (isMaxLevel) {
      return null;
    }

    return (
      <ProgressBar
        label="Skill XP"
        aria_labelledby={xpLabelId}
        value={skill.xp}
        max={skill.xp_max}
        value_label={`${skill.xp} / ${skill.xp_max}`}
        variant={ProgressBarVariant.XP}
      />
    );
  };

  const renderClassBonus = (): ReactNode => {
    if (skill.class_bonus <= 0) {
      return null;
    }

    return (
      <>
        <Dt>Class Bonus</Dt>
        <Dd>{formatSkillPercentage(skill.class_bonus)}</Dd>
      </>
    );
  };

  const renderTrainingAllocation = (): ReactNode => {
    if (!skill.is_training) {
      return null;
    }

    return (
      <>
        <Dt>XP Sacrificed To Training</Dt>
        <Dd>{formatSkillPercentage(skill.xp_towards)}</Dd>
      </>
    );
  };

  const renderModifierRow = (modifierRow: SkillFactRowDefinition) => (
    <React.Fragment key={modifierRow.key}>
      <Dt>{modifierRow.label}</Dt>
      <Dd>{modifierRow.value}</Dd>
    </React.Fragment>
  );

  const renderModifiers = (): ReactNode => {
    if (modifierRows.length === 0) {
      return null;
    }

    return (
      <section aria-label="Skill modifiers" className="flex flex-col gap-2">
        <h3 className="font-semibold text-gray-900 dark:text-gray-100">
          Modifiers
        </h3>
        <Dl>{modifierRows.map(renderModifierRow)}</Dl>
      </section>
    );
  };

  return (
    <div className="flex flex-col gap-4">
      {renderDescription()}
      <Dl>
        <Dt>Level</Dt>
        <Dd>
          {skill.level} / {skill.max_level}
        </Dd>
        <Dt>Type</Dt>
        <Dd>{skill.skill_type}</Dd>
        <Dt>Skill Bonus</Dt>
        <Dd>{formatSkillPercentage(skill.skill_bonus)}</Dd>
        <Dt>Skill XP Bonus</Dt>
        <Dd>{formatSkillPercentage(skill.skill_xp_bonus)}</Dd>
        {renderClassBonus()}
        <Dt>Locked</Dt>
        <Dd>{skill.is_locked ? 'Yes' : 'No'}</Dd>
        <Dt>Training</Dt>
        <Dd>{skill.is_training ? 'Yes' : 'No'}</Dd>
        {renderTrainingAllocation()}
      </Dl>
      {renderXp()}
      {renderModifiers()}
    </div>
  );
};

export default SkillDetailFacts;
