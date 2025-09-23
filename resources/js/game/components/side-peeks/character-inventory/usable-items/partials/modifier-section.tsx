import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import ModifiersSectionProps from '../types/partials/modifier-section-props';

const ModifiersSection = ({ item, showSeparator }: ModifiersSectionProps) => {
  return (
    <Section title="Modifiers" showSeparator={showSeparator}>
      <DefinitionRow
        left={<InfoLabel label="Base Damage Mod" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.base_damage_mod!}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Base Healing Mod" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.base_healing_mod!}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Base AC Mod" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.base_ac_mod!}
          </span>
        }
      />
    </Section>
  );
};

export default ModifiersSection;
