import React, { ReactNode } from 'react';

import {
  passiveSkillCardBaseStyles,
  passiveSkillCardInteractiveStyles,
  passiveSkillCardSecondaryTextStyles,
} from '../styles/passive-skill-card-styles';
import PassiveSkillCardProps from '../types/passive-skill-card-props';

const PassiveSkillCard = ({
  passive_skill_id: passiveSkillId,
  name,
  effect_label: effectLabel,
  unlocks_at_level: unlocksAtLevel,
  max_level: maxLevel,
  on_open_passive_skill: onOpenPassiveSkill,
}: PassiveSkillCardProps): ReactNode => {
  const renderContent = (): ReactNode => (
    <>
      <i className="ra ra-book text-2xl" aria-hidden="true" />
      <span className="flex min-w-0 flex-1 flex-col gap-1">
        <span className="text-sm font-semibold break-words">{name}</span>
        <span
          className={`flex flex-col gap-0.5 text-xs ${passiveSkillCardSecondaryTextStyles()}`}
        >
          <span>{effectLabel}</span>
          {typeof unlocksAtLevel === 'number' && unlocksAtLevel > 0 && (
            <span>Unlocks At Level: {unlocksAtLevel}</span>
          )}
          {typeof maxLevel === 'number' && maxLevel > 0 && (
            <span>Max Level: {maxLevel}</span>
          )}
        </span>
      </span>
    </>
  );

  if (onOpenPassiveSkill) {
    return (
      <button
        type="button"
        onClick={() => onOpenPassiveSkill(passiveSkillId)}
        aria-label={`Open Passive Skill details for ${name}`}
        className={`${passiveSkillCardBaseStyles()} ${passiveSkillCardInteractiveStyles()}`}
      >
        {renderContent()}
      </button>
    );
  }

  return (
    <article aria-label={name} className={passiveSkillCardBaseStyles()}>
      {renderContent()}
    </article>
  );
};

export default PassiveSkillCard;
