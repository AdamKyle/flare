import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';
import { getRaidMonsterSpecialAttackInfo } from '../util/get-raid-monster-special-attack-info';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const MonsterRaidSpecialAttackSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  if (!monster.is_raid_boss) {
    return null;
  }

  const info = getRaidMonsterSpecialAttackInfo(
    monster.raid_special_attack_type
  );

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Raid Special Attack
      </h3>
      <Separator />
      <Dl>
        <Dt>Attack Name:</Dt>
        <Dd>{info.name}</Dd>
        <Dt>Details:</Dt>
        <Dd>{info.details}</Dd>
      </Dl>
    </>
  );
};

export default MonsterRaidSpecialAttackSection;
