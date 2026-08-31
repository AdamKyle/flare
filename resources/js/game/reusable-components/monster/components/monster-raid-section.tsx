import React, { ReactNode } from 'react';

import {
  isRaidAttackType,
  RAID_ATTACK_TYPE_LABELS,
} from '../enums/raid-attack-type';
import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterRaidSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { raid_and_special: raid } = monster;

  const renderAttackType = (): ReactNode => {
    if (raid.raid_special_attack_type === null) {
      return 'None';
    }

    return isRaidAttackType(raid.raid_special_attack_type)
      ? RAID_ATTACK_TYPE_LABELS[raid.raid_special_attack_type]
      : raid.raid_special_attack_type;
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Raid &amp; Special Rules
      </h2>
      <Dl>
        <Dt>Raid Monster</Dt>
        <Dd>{raid.is_raid_monster ? 'Yes' : 'No'}</Dd>
        <Dt>Raid Boss</Dt>
        <Dd>{raid.is_raid_boss ? 'Yes' : 'No'}</Dd>
        <Dt>Special Attack Type</Dt>
        <Dd>{renderAttackType()}</Dd>
        <Dt>Fire Atonement</Dt>
        <Dd>{raid.fire_atonement ?? 0}</Dd>
        <Dt>Ice Atonement</Dt>
        <Dd>{raid.ice_atonement ?? 0}</Dd>
        <Dt>Water Atonement</Dt>
        <Dd>{raid.water_atonement ?? 0}</Dd>
      </Dl>
    </Card>
  );
};

export default MonsterRaidSection;
