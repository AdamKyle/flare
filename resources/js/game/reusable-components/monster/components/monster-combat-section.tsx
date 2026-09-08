import React, { ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
} from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterCombatSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { combat, probabilities } = monster;

  const coreStatRows: { label: string; value: number }[] = [
    { label: 'Strength', value: combat.str },
    { label: 'Durability', value: combat.dur },
    { label: 'Dexterity', value: combat.dex },
    { label: 'Charisma', value: combat.chr },
    { label: 'Intelligence', value: combat.int },
    { label: 'Agility', value: combat.agi },
    { label: 'Focus', value: combat.focus },
    { label: 'Armor Class', value: combat.ac },
  ].filter((row) => row.value !== 0);

  const probabilityRows: { label: string; value: number }[] = [
    { label: 'Accuracy', value: probabilities.accuracy ?? 0 },
    { label: 'Dodge', value: probabilities.dodge ?? 0 },
    { label: 'Criticality', value: probabilities.criticality ?? 0 },
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

  if (coreStatRows.length === 0 && probabilityRows.length === 0) {
    return null;
  }

  return (
    <div>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Core Combat
      </h2>
      <Dl>
        {coreStatRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatNumberWithCommas(row.value)}</Dd>
          </React.Fragment>
        ))}
        {probabilityRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatPercent(row.value)}</Dd>
          </React.Fragment>
        ))}
      </Dl>
    </div>
  );
};

export default MonsterCombatSection;
