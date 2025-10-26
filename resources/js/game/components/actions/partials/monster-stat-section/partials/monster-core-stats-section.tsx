import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const MonsterCoreStatsSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300">
        Core Stats
      </h3>
      <Separator />
      <Dl>
        <Dt>Str:</Dt>
        <Dd>{formatNumberWithCommas(monster.str)}</Dd>

        <Dt>Dur:</Dt>
        <Dd>{formatNumberWithCommas(monster.dur)}</Dd>

        <Dt>Dex:</Dt>
        <Dd>{formatNumberWithCommas(monster.dex)}</Dd>

        <Dt>Int:</Dt>
        <Dd>{formatNumberWithCommas(monster.int)}</Dd>

        <Dt>Chr:</Dt>
        <Dd>{formatNumberWithCommas(monster.chr)}</Dd>

        <Dt>Agi:</Dt>
        <Dd>{formatNumberWithCommas(monster.agi)}</Dd>

        <Dt>Focus:</Dt>
        <Dd>{formatNumberWithCommas(monster.focus)}</Dd>
      </Dl>
    </>
  );
};

export default MonsterCoreStatsSection;
