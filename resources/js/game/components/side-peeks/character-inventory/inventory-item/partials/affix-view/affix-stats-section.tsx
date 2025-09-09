import { isNil } from 'lodash';
import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import { formatSignedPercent } from '../../../../../../util/format-number';
import AffixStatsSectionProps from '../../types/partials/affix-view/affix-stats-section-props';

const AffixStatsSection = ({ affix }: AffixStatsSectionProps) => {
  const buildAttributeTooltip = (attributeLabel: string) => {
    return `This ${attributeLabel.toLowerCase()} is applied directly to the item's ${attributeLabel.toLowerCase()}, which in turn is applied to the character. This value stacks additively with other affixes; the combined total is applied to the item's ${attributeLabel.toLowerCase()}.`;
  };

  const rows = [
    { label: 'Strength', value: affix.str_mod },
    { label: 'Dexterity', value: affix.dex_mod },
    { label: 'Intelligence', value: affix.int_mod },
    { label: 'Charisma', value: affix.chr_mod },
    { label: 'Agility', value: affix.agi_mod },
    { label: 'Durability', value: affix.dur_mod },
    { label: 'Focus', value: affix.focus_mod },
  ];

  const allZero = rows.every(({ value }) => Number(value ?? 0) === 0);

  if (allZero) {
    return null;
  }

  const renderRow = (label: string, value: number | null) => {
    if (isNil(value) || value === 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label={label}
            tooltip={buildAttributeTooltip(label)}
            tooltipValue={Number(value)}
            tooltipAlign="right"
            tooltipRenderAsPercent
            tooltipSize="sm"
          />
        }
        right={
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(Number(value))}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <Section title="Stats">
      {rows.map(({ label, value }) => renderRow(label, value))}
    </Section>
  );
};

export default AffixStatsSection;
