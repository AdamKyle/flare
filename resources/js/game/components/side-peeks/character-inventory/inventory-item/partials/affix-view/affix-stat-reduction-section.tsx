import { isNil } from 'lodash';
import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import AffixStatsReductionSectionProps from '../../types/partials/affix-view/affix-stats-reduction-section-props';

import { formatSignedPercent } from 'game-utils/format-number';

const AffixStatsReductionSection = ({
  affix,
}: AffixStatsReductionSectionProps) => {
  const rows = [
    { label: 'Strength', value: affix.str_reduction },
    { label: 'Dexterity', value: affix.dex_reduction },
    { label: 'Intelligence', value: affix.int_reduction },
    { label: 'Charisma', value: affix.chr_reduction },
    { label: 'Agility', value: affix.agi_reduction },
    { label: 'Durability', value: affix.dur_reduction },
    { label: 'Focus', value: affix.focus_reduction },
  ];

  const allZero = rows.every(({ value }) => Number(value ?? 0) === 0);

  if (allZero) {
    return null;
  }

  const buildAttributeTooltip = (attributeLabel: string, value: number) => {
    return `This will reduce the enemies ${attributeLabel.toLowerCase()} by ${formatSignedPercent(
      value
    )} at the time of fight initiation. This stacks with simmilar affixes and last the entire duration of the battle.`;
  };

  const renderRow = (label: string, value: number | null) => {
    if (isNil(value) || value === 0) {
      return null;
    }

    const numericValue = Number(value);

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label={label}
            tooltip={buildAttributeTooltip(label, numericValue)}
            tooltipValue={numericValue}
            tooltipAlign="right"
            tooltipRenderAsPercent
            tooltipSize="sm"
          />
        }
        right={
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-down text-rose-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-rose-700 tabular-nums">
              {formatSignedPercent(numericValue)}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <Section title="Stat Reductions">
      {rows.map(({ label, value }) => renderRow(label, value))}
    </Section>
  );
};

export default AffixStatsReductionSection;
