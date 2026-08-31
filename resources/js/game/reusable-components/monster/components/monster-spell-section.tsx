import React, { ReactNode } from 'react';

import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterSpellSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { spells_and_affixes: spells } = monster;

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Spells &amp; Affixes
      </h2>
      <Dl>
        <Dt>Can Cast</Dt>
        <Dd>{spells.can_cast ? 'Yes' : 'No'}</Dd>
        <Dt>Max Spell Damage</Dt>
        <Dd>{spells.max_spell_damage ?? 0}</Dd>
        <Dt>Casting Accuracy</Dt>
        <Dd>{spells.casting_accuracy ?? 0}</Dd>
        <Dt>Spell Evasion</Dt>
        <Dd>{spells.spell_evasion ?? 0}</Dd>
        <Dt>Max Affix Damage</Dt>
        <Dd>{spells.max_affix_damage ?? 0}</Dd>
        <Dt>Affix Resistance</Dt>
        <Dd>{spells.affix_resistance ?? 0}</Dd>
        <Dt>Healing Percentage</Dt>
        <Dd>{spells.healing_percentage ?? 0}</Dd>
        <Dt>Entrancing Chance</Dt>
        <Dd>{spells.entrancing_chance ?? 0}</Dd>
        <Dt>Devouring Light Chance</Dt>
        <Dd>{spells.devouring_light_chance ?? 0}</Dd>
        <Dt>Devouring Darkness Chance</Dt>
        <Dd>{spells.devouring_darkness_chance ?? 0}</Dd>
        <Dt>Life Stealing Resistance</Dt>
        <Dd>{spells.life_stealing_resistance ?? 0}</Dd>
      </Dl>
    </Card>
  );
};

export default MonsterSpellSection;
