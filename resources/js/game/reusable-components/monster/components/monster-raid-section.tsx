import React, { ReactNode } from 'react';

import { formatPercent } from '../../../util/format-number';
import {
  isRaidAttackType,
  RAID_ATTACK_TYPE_LABELS,
} from '../enums/raid-attack-type';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterRaidSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { raid_and_special: raid } = monster;

  const renderAttackTypeLabel = (value: number): ReactNode =>
    isRaidAttackType(value) ? RAID_ATTACK_TYPE_LABELS[value] : value;

  const atonementRows: { label: string; value: number }[] = [
    { label: 'Fire Atonement', value: raid.fire_atonement ?? 0 },
    { label: 'Ice Atonement', value: raid.ice_atonement ?? 0 },
    { label: 'Water Atonement', value: raid.water_atonement ?? 0 },
  ].filter((row) => row.value > 0);

  const hasRows =
    raid.is_raid_monster ||
    raid.is_raid_boss ||
    raid.raid_special_attack_type !== null ||
    atonementRows.length > 0;

  if (!hasRows) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Raid / Special
      </h2>
      <Dl>
        {raid.is_raid_monster && (
          <>
            <Dt>Raid Monster</Dt>
            <Dd>Yes</Dd>
          </>
        )}
        {raid.is_raid_boss && (
          <>
            <Dt>Raid Boss</Dt>
            <Dd>Yes</Dd>
          </>
        )}
        {raid.raid_special_attack_type !== null && (
          <>
            <Dt>Special Attack Type</Dt>
            <Dd>{renderAttackTypeLabel(raid.raid_special_attack_type)}</Dd>
          </>
        )}
        {atonementRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{formatPercent(row.value)}</Dd>
          </React.Fragment>
        ))}
      </Dl>
    </div>
  );
};

export default MonsterRaidSection;
