import React, { ReactNode } from 'react';

import { formatNumberWithCommas } from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterCombatSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { combat } = monster;

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

  if (coreStatRows.length === 0) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Core Combat
      </h2>
      <Dl>
        {coreStatRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatNumberWithCommas(row.value)}</Dd>
          </React.Fragment>
        ))}
      </Dl>
    </div>
  );
};

export default MonsterCombatSection;
