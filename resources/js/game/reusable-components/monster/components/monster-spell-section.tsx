import React, { ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
} from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterSpellSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { spells_and_affixes: spells } = monster;

  const damageRows: { label: string; value: number }[] = [
    { label: 'Max Spell Damage', value: spells.max_spell_damage ?? 0 },
    { label: 'Max Affix Damage', value: spells.max_affix_damage ?? 0 },
  ].filter((row) => row.value !== 0);

  const percentRows: { label: string; value: number }[] = [
    { label: 'Casting Accuracy', value: spells.casting_accuracy ?? 0 },
    { label: 'Spell Evasion', value: spells.spell_evasion ?? 0 },
    { label: 'Affix Resistance', value: spells.affix_resistance ?? 0 },
    { label: 'Healing Percentage', value: spells.healing_percentage ?? 0 },
    { label: 'Entrancing Chance', value: spells.entrancing_chance ?? 0 },
    {
      label: 'Devouring Light Chance',
      value: spells.devouring_light_chance ?? 0,
    },
    {
      label: 'Devouring Darkness Chance',
      value: spells.devouring_darkness_chance ?? 0,
    },
    {
      label: 'Life Stealing Resistance',
      value: spells.life_stealing_resistance ?? 0,
    },
  ].filter((row) => row.value !== 0);

  const hasRows =
    spells.can_cast || damageRows.length > 0 || percentRows.length > 0;

  if (!hasRows) {
    return null;
  }

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
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
        {percentRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatPercent(row.value)}</Dd>
          </React.Fragment>
        ))}
      </Dl>
    </Card>
  );
};

export default MonsterSpellSection;
