import React from 'react';

import DefinitionRow from './definition-row';
import InfoLabel from './info-label';
import StatRowIntegerProps from './types/stat-row-integer-props';
import { formatIntWithPlus } from '../../util/format-number';

const StatRowInteger = ({
  label,
  value,
  tooltip,
  tooltipValue = value,
  tooltipAlign = 'right',
  tooltipSize = 'sm',
}: StatRowIntegerProps) => {
  if (value <= 0) {
    return null;
  }

  return (
    <DefinitionRow
      left={
        <InfoLabel
          label={label}
          tooltip={tooltip}
          tooltipValue={tooltipValue}
          tooltipAlign={tooltipAlign}
          tooltipSize={tooltipSize}
        />
      }
      right={
        <span className="inline-flex items-center gap-2 whitespace-nowrap">
          <i
            className="fas fa-chevron-up text-emerald-600"
            aria-hidden="true"
          />
          <span className="font-semibold text-emerald-700 tabular-nums dark:text-emerald-400">
            {formatIntWithPlus(value)}
          </span>
        </span>
      }
    />
  );
};

export default StatRowInteger;
