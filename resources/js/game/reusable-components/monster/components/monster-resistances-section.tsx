import React, { ReactNode } from 'react';

import { formatPercent } from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterResistancesSection = ({
  monster,
}: MonsterDetailProps): ReactNode => {
  const { spells_and_affixes: spells } = monster;

  const rows: { label: string; value: number }[] = [
    { label: 'Affix Resistance', value: spells.affix_resistance ?? 0 },
    {
      label: 'Life Stealing Resistance',
      value: spells.life_stealing_resistance ?? 0,
    },
  ].filter((row) => row.value !== 0);

  if (rows.length === 0) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Resistances
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

export default MonsterResistancesSection;
