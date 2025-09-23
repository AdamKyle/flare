import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../../util/format-number';
import ExperienceBonusesSectionProps from '../types/partials/experience-bonus-section-props';

const ExperienceBonusesSection = ({
  item,
  showSeparator,
}: ExperienceBonusesSectionProps) => {
  const renderExperienceBonus = () => {
    if (!item.xp_bonus) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label="XP Bonus" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {formatPercent(item.xp_bonus)}
          </span>
        }
      />
    );
  };

  return (
    <Section title="Experience Bonuses" showSeparator={showSeparator}>
      <DefinitionRow
        left={<InfoLabel label="Gains Additional Levels on Level Up" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.gain_additional_level ? 'Yes' : 'No'}
          </span>
        }
      />
      {renderExperienceBonus()}
    </Section>
  );
};

export default ExperienceBonusesSection;
