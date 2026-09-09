import React, { ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
} from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterSpellSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { spells_and_affixes: spells } = monster;

  const damageRows: { label: string; value: number }[] = [
    { label: 'Max Spell Damage', value: spells.max_spell_damage ?? 0 },
    { label: 'Max Affix Damage', value: spells.max_affix_damage ?? 0 },
  ].filter((row) => row.value !== 0);

  const entrancingChance = spells.entrancing_chance ?? 0;

  const hasRows =
    spells.can_cast || damageRows.length > 0 || entrancingChance !== 0;

  if (!hasRows) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Spells &amp; Affixes
      </h2>
      <Dl>
        {spells.can_cast && (
          <>
            <Dt>Can Cast</Dt>
            <Dd>Yes</Dd>
          </>
        )}
        {damageRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatNumberWithCommas(row.value)}</Dd>
          </React.Fragment>
        ))}
        {entrancingChance !== 0 && (
          <>
            <Dt>Entrancing Chance</Dt>
            <Dd>{formatPercent(entrancingChance)}</Dd>
          </>
        )}
      </Dl>
    </div>
  );
};

export default MonsterSpellSection;
