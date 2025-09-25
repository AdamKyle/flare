import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../../util/format-number';
import StatIncreaseSectionProps from '../types/partials/stat-increase-section-props';

const StatIncreaseSection = ({
  item,
  showSeparator,
  showTitleSeparator,
}: StatIncreaseSectionProps) => {
  return (
    <Section
      title="Stat Increase"
      showSeparator={showSeparator}
      showTitleSeparator={showTitleSeparator}
    >
      <DefinitionRow
        left={<InfoLabel label="Increases Stat by" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {formatPercent(item.stat_increase)}
          </span>
        }
      />
    </Section>
  );
};

export default StatIncreaseSection;
