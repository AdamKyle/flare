import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../reusable-components/viewable-sections/stat-row-percent';
import SkillModifiersSectionProps from '../types/partials/skill-modifiers-section-props';

const SkillModifiersSection = ({
  item,
  showSeparator,
  showTitleSeparator,
}: SkillModifiersSectionProps) => {
  return (
    <Section
      title="Skill Modifiers"
      showSeparator={showSeparator}
      showTitleSeparator={showTitleSeparator}
    >
      <StatRowPercent
        label="Skill Bonus"
        value={item.increase_skill_bonus_by!}
      />

      <StatRowPercent
        label="Skill XP Bonus"
        value={item.increase_skill_training_bonus_by!}
      />

      <DefinitionRow
        left={<InfoLabel label="Affected Skills" />}
        right={
          <span className="text-sm leading-relaxed text-gray-800 dark:text-gray-200">
            {item.skills.join(', ')}
          </span>
        }
      />
    </Section>
  );
};

export default SkillModifiersSection;
