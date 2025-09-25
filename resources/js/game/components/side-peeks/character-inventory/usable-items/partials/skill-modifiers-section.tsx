import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../../util/format-number';
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
      <DefinitionRow
        left={<InfoLabel label="Skill" />}
        right={
          <span className="text-gray-800 dark:text-gray-200 whitespace-nowrap">
            +{formatPercent(item.increase_skill_bonus_by!)}
          </span>
        }
      />

      <DefinitionRow
        left={<InfoLabel label="Training" />}
        right={
          <span className="text-gray-800 dark:text-gray-200 whitespace-nowrap">
            +{formatPercent(item.increase_skill_training_bonus_by!)}
          </span>
        }
      />

      <DefinitionRow
        left={<InfoLabel label="Affected Skills" />}
        right={
          <span
            className="
              text-gray-800 dark:text-gray-200
              inline-block
              max-w-2/3
              whitespace-normal break-words text-sm leading-relaxed text-left align-top
              mr-[-10px]
            "
          >
            {item.skills.join(', ')}
          </span>
        }
      />
    </Section>
  );
};

export default SkillModifiersSection;
