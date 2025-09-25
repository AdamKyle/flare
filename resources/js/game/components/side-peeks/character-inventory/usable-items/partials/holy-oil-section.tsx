import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import HolyOilSectionProps from '../types/partials/holy-oil-section-props';

const HolyOilSection = ({ item, showTitleSeparator }: HolyOilSectionProps) => {
  return (
    <Section
      title="Holy Blessing"
      showSeparator={false}
      showTitleSeparator={showTitleSeparator}
    >
      <DefinitionRow
        left={<InfoLabel label="Holy Level" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.holy_level!}
          </span>
        }
      />
    </Section>
  );
};

export default HolyOilSection;
