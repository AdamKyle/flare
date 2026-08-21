import React, { ReactNode } from 'react';

import CraftingSkillProgressListProps from './types/crafting-skill-progress-list-props';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CraftingSkillProgressList = ({
  skills,
}: CraftingSkillProgressListProps): ReactNode => {
  return (
    <div className="space-y-2">
      {skills.map((skill) =>
        skill.is_maxed ? (
          <p key={skill.crafting_type} className="text-sm">
            <span className="font-semibold">{skill.skill_name}</span>{' '}
            <span className="text-gray-600 dark:text-gray-400">
              (Level {skill.level} / {skill.max_level}) — Maxed
            </span>
          </p>
        ) : (
          <ProgressBar
            key={skill.crafting_type}
            value={skill.current_xp}
            max={skill.next_level_xp}
            label={`${skill.skill_name} (Level ${skill.level} / ${skill.max_level})`}
            value_label={`${skill.current_xp} / ${skill.next_level_xp} XP`}
            variant={ProgressBarVariant.PRIMARY}
          />
        )
      )}
    </div>
  );
};

export default CraftingSkillProgressList;
