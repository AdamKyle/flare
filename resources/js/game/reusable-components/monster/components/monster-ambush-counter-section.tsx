import React, { ReactNode } from 'react';

import { formatPercent } from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterAmbushCounterSection = ({
  monster,
}: MonsterDetailProps): ReactNode => {
  const { probabilities } = monster;

  const rows: { label: string; value: number }[] = [
    { label: 'Ambush Chance', value: probabilities.ambush_chance ?? 0 },
    {
      label: 'Ambush Resistance',
      value: probabilities.ambush_resistance ?? 0,
    },
    { label: 'Counter Chance', value: probabilities.counter_chance ?? 0 },
    {
      label: 'Counter Resistance',
      value: probabilities.counter_resistance ?? 0,
    },
  ].filter((row) => row.value !== 0);

  if (rows.length === 0) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Ambush &amp; Counter
      </h2>
      <Dl>
        {rows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatPercent(row.value)}</Dd>
          </React.Fragment>
        ))}
      </Dl>
    </div>
  );
};

export default MonsterAmbushCounterSection;
