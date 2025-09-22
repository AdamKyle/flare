import React from 'react';

import { formatPercent } from '../../../util/format-number';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoAlerts from '../../viewable-sections/info-alert';
import InfoLabel from '../../viewable-sections/info-label';
import Section from '../../viewable-sections/section';
import ModifiersSectionProps from '../types/partials/modifer-section-props';

const ModifiersSection = ({ item, showSeparator }: ModifiersSectionProps) => {
  const hasMove = item.move_time_out_mod_bonus !== 0;
  const hasFight = item.fight_time_out_mod_bonus !== 0;

  if (!hasMove && !hasFight) {
    return null;
  }

  const messages: string[] = [];
  if (hasFight) {
    messages.push(
      'This quest item stacks with other items and skills to decrease the time-out between manual fights.'
    );
  }
  if (hasMove) {
    messages.push(
      'This quest item stacks with other items and skills to decrease the time between movement on all maps, including teleport and directional movement.'
    );
  }

  const renderModifierRow = (label: string, percentValue: number) => {
    if (percentValue === 0) {
      return null;
    }

    const proportion = percentValue / 100;

    return (
      <DefinitionRow
        left={<InfoLabel label={label} />}
        right={
          <span className="font-semibold text-emerald-700 dark:text-emerald-300">
            {formatPercent(proportion)}
          </span>
        }
      />
    );
  };

  return (
    <Section
      title="Modifiers"
      showSeparator={showSeparator}
      lead={<InfoAlerts messages={messages} />}
    >
      {renderModifierRow(
        'Fight Timeout Modifier',
        item.fight_time_out_mod_bonus
      )}
      {renderModifierRow('Move Timeout Modifier', item.move_time_out_mod_bonus)}
    </Section>
  );
};

export default ModifiersSection;
