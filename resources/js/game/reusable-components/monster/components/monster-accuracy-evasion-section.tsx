import React, { ReactNode } from 'react';

import { formatPercent } from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterAccuracyEvasionSection = ({
  monster,
}: MonsterDetailProps): ReactNode => {
  const { probabilities, spells_and_affixes: spells } = monster;

  const rows: { label: string; value: number }[] = [
    { label: 'Accuracy', value: probabilities.accuracy ?? 0 },
    { label: 'Dodge', value: probabilities.dodge ?? 0 },
    { label: 'Criticality', value: probabilities.criticality ?? 0 },
    { label: 'Casting Accuracy', value: spells.casting_accuracy ?? 0 },
    { label: 'Spell Evasion', value: spells.spell_evasion ?? 0 },
  ].filter((row) => row.value !== 0);

  if (rows.length === 0) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Accuracy &amp; Evasion
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

export default MonsterAccuracyEvasionSection;
