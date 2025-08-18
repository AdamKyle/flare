import React from 'react';

import StatInfoToolTip from '../../stat-info-tool-tip';
import { formatSignedPercent, isPositiveNumber } from '../../utils/item-view';

import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';

const StatsAttributesBlockRow = ({
  label,
  value,
}: {
  label: string;
  value: number;
}) => {
  if (!isPositiveNumber(value)) {
    return null;
  }

  return (
    <>
      <Dt>
        <StatInfoToolTip
          label={label}
          value={value}
          renderAsPercent
          align="left"
          size="sm"
        />
        <span className="min-w-0 break-words">{label}</span>
      </Dt>
      <Dd>
        <span className="font-semibold text-emerald-600 dark:text-emerald-400">
          {formatSignedPercent(value)}
        </span>
      </Dd>
    </>
  );
};

export default StatsAttributesBlockRow;
