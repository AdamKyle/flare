import React, { ReactNode } from 'react';

import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterCombatSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { combat, probabilities } = monster;

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Core Combat
      </h2>
      <Dl>
        <Dt>Strength</Dt>
        <Dd>{combat.str}</Dd>
        <Dt>Durability</Dt>
        <Dd>{combat.dur}</Dd>
        <Dt>Dexterity</Dt>
        <Dd>{combat.dex}</Dd>
        <Dt>Charisma</Dt>
        <Dd>{combat.chr}</Dd>
        <Dt>Intelligence</Dt>
        <Dd>{combat.int}</Dd>
        <Dt>Agility</Dt>
        <Dd>{combat.agi}</Dd>
        <Dt>Focus</Dt>
        <Dd>{combat.focus}</Dd>
        <Dt>Armor Class</Dt>
        <Dd>{combat.ac}</Dd>
        <Dt>Accuracy</Dt>
        <Dd>{probabilities.accuracy ?? 0}</Dd>
        <Dt>Dodge</Dt>
        <Dd>{probabilities.dodge ?? 0}</Dd>
        <Dt>Criticality</Dt>
        <Dd>{probabilities.criticality ?? 0}</Dd>
        <Dt>Ambush Chance</Dt>
        <Dd>{probabilities.ambush_chance ?? 0}</Dd>
        <Dt>Ambush Resistance</Dt>
        <Dd>{probabilities.ambush_resistance ?? 0}</Dd>
        <Dt>Counter Chance</Dt>
        <Dd>{probabilities.counter_chance ?? 0}</Dd>
        <Dt>Counter Resistance</Dt>
        <Dd>{probabilities.counter_resistance ?? 0}</Dd>
      </Dl>
    </Card>
  );
};

export default MonsterCombatSection;
