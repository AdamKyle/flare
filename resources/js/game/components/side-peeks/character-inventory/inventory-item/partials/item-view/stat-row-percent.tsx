import clsx from 'clsx';
import React from 'react';

import DefinitionRow from './definition-row';
import InfoLabel from './info-label';
import { formatPercent } from '../../../../../../util/format-number';
import StatRowPercentProps from '../../types/partials/item-view/stat-row-percent-props';

const StatRowPercent = ({
  label,
  value,
  tooltip,
  tooltipValue = value,
  tooltipAlign = 'right',
  tooltipRenderAsPercent = true,
  tooltipSize = 'sm',
  negative = false,
}: StatRowPercentProps) => {
  if (value <= 0 && !negative) {
    return null;
  }

  const iconClass = negative
    ? 'fas fa-chevron-down text-rose-600'
    : 'fas fa-chevron-up text-emerald-600';
  const valueClass = negative
    ? 'font-semibold text-rose-600 tabular-nums'
    : 'font-semibold text-emerald-700 tabular-nums';

  return (
    <DefinitionRow
      left={
        <InfoLabel
          label={label}
          tooltip={tooltip}
          tooltipValue={tooltipValue}
          tooltipAlign={tooltipAlign}
          tooltipRenderAsPercent={tooltipRenderAsPercent}
          tooltipSize={tooltipSize}
        />
      }
      right={
        <span className="inline-flex items-center gap-2 whitespace-nowrap">
          <i className={clsx(iconClass)} aria-hidden="true" />
          <span className={clsx(valueClass)}>
            {negative ? `-${formatPercent(value)}` : formatPercent(value)}
          </span>
        </span>
      }
    />
  );
};

export default StatRowPercent;
