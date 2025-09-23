import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import GeneralSectionProps from '../types/partials/general-section-props';

const GeneralSection = ({ item, showSeparator }: GeneralSectionProps) => {
  return (
    <Section title="General" showSeparator={showSeparator}>
      <DefinitionRow
        left={<InfoLabel label="Can Stack" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.can_stack ? 'Yes' : 'No'}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Lasts For (Minutes)" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.lasts_for}
          </span>
        }
      />
    </Section>
  );
};

export default GeneralSection;
