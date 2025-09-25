import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import KingdomEffectsSectionProps from '../types/partials/kingdom-effects-section-props';

const KingdomEffectsSection = ({
  item,
  showTitleSeparator,
}: KingdomEffectsSectionProps) => {
  return (
    <Section
      title="Kingdom Effects"
      showSeparator={false}
      showTitleSeparator={showTitleSeparator}
    >
      <DefinitionRow
        left={<InfoLabel label="Damages Kingdoms" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.damages_kingdoms ? 'Yes' : 'No'}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Kingdom Damage" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.kingdom_damage!}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Lasts For (Minutes)" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.lasts_for!}
          </span>
        }
      />
    </Section>
  );
};

export default KingdomEffectsSection;
