import React from 'react';

import DefinitionRow from '../../../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../../../reusable-components/viewable-sections/section';
import { formatSignedPercent } from '../../../../../../util/format-number';
import { STAT_FIELDS } from '../../types/partials/constants/stat-fields';
import StatsSectionProps from '../../types/partials/item-view/stats-section-props';

const StatsSection = ({ item }: StatsSectionProps) => {
  const rows = STAT_FIELDS.map(({ key, label }) => ({
    label,
    value: Number(item[key] ?? 0),
  }));

  const allZero = rows.every(({ value }) => value === 0);

  if (allZero) {
    return null;
  }

  const renderUpIcon = (value: number) => {
    if (value <= 0) {
      return null;
    }

    return (
      <i className="fas fa-chevron-up text-emerald-600" aria-hidden="true" />
    );
  };

  const renderStatRow = (label: string, value: number) => {
    if (value === 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={
          <InfoLabel
            label={label}
            tooltip={label}
            tooltipValue={value}
            tooltipAlign="right"
            tooltipRenderAsPercent
          />
        }
        right={
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            {renderUpIcon(value)}
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(value)}
            </span>
          </span>
        }
      />
    );
  };

  return (
    <Section title="Stats">
      {rows.map(({ label, value }) => renderStatRow(label, value))}
    </Section>
  );
};

export default StatsSection;
