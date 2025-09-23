import React from 'react';

import DefinitionRow from '../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../../util/format-number';
import MiscModifiersSectionProps from '../types/partials/misc-modifier-section-props';

const MiscModifiersSection = ({
  item,
  showSeparator,
}: MiscModifiersSectionProps) => {
  return (
    <Section title="Misc Modifiers" showSeparator={showSeparator}>
      <DefinitionRow
        left={<InfoLabel label="Fight Timeout Modifier" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {formatPercent(item.fight_time_out_mod_bonus! / 100)}
          </span>
        }
      />
      <DefinitionRow
        left={<InfoLabel label="Move Timeout Modifier" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {formatPercent(item.move_time_out_mod_bonus! / 100)}
          </span>
        }
      />
    </Section>
  );
};

export default MiscModifiersSection;
