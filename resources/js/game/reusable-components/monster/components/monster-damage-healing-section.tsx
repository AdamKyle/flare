import React, { ReactNode } from 'react';

import {
  formatPercent,
  formatRangeWithCommas,
} from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterDamageHealingSection = ({
  monster,
}: MonsterDetailProps): ReactNode => {
  const { identity, spells_and_affixes: spells } = monster;

  const healingPercentage = spells.healing_percentage ?? 0;

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Damage &amp; Healing
      </h2>
      <Dl>
        <Dt>Health Range</Dt>
        <Dd>{formatRangeWithCommas(identity.health_range)}</Dd>
        <Dt>Attack Range</Dt>
        <Dd>{formatRangeWithCommas(identity.attack_range)}</Dd>
        {healingPercentage !== 0 && (
          <>
            <Dt>Healing Percentage</Dt>
            <Dd>{formatPercent(healingPercentage)}</Dd>
          </>
        )}
      </Dl>
    </div>
  );
};

export default MonsterDamageHealingSection;
