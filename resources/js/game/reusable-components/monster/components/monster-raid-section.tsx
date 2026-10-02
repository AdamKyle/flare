import React, { ReactNode } from 'react';

import {
  isRaidAttackType,
  RAID_ATTACK_TYPE_LABELS,
} from '../enums/raid-attack-type';
import MonsterDetailProps from '../types/monster-detail-props';
import MonsterElementalRow from '../types/monster-elemental-row';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterRaidSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { raid_and_special: raid } = monster;

  const atonementRows: MonsterElementalRow[] = [
    {
      element_name: 'Fire',
      label: 'Fire Atonement',
      value: raid.fire_atonement ?? 0,
    },
    {
      element_name: 'Ice',
      label: 'Ice Atonement',
      value: raid.ice_atonement ?? 0,
    },
    {
      element_name: 'Water',
      label: 'Water Atonement',
      value: raid.water_atonement ?? 0,
    },
  ].filter((row) => row.value > 0);

  const penetrationRows: MonsterElementalRow[] = [
    {
      element_name: 'Fire',
      label: 'Fire Penetration',
      value: raid.fire_penetration ?? 0,
    },
    {
      element_name: 'Ice',
      label: 'Ice Penetration',
      value: raid.ice_penetration ?? 0,
    },
    {
      element_name: 'Water',
      label: 'Water Penetration',
      value: raid.water_penetration ?? 0,
    },
  ].filter((row) => row.value > 0);

  const hasRows =
    raid.is_raid_monster ||
    raid.is_raid_boss ||
    raid.raid_special_attack_type !== null ||
    atonementRows.length > 0 ||
    penetrationRows.length > 0;

  if (!hasRows) {
    return null;
  }

  const renderFlagRow = (label: string, isShown: boolean): ReactNode => {
    if (!isShown) {
      return null;
    }

    return (
      <>
        <Dt>{label}</Dt>
        <Dd>Yes</Dd>
      </>
    );
  };

  const renderSpecialAttackType = (): ReactNode => {
    if (raid.raid_special_attack_type === null) {
      return null;
    }

    const attackType = raid.raid_special_attack_type;

    return (
      <>
        <Dt>Special Attack Type</Dt>
        <Dd>
          {isRaidAttackType(attackType)
            ? RAID_ATTACK_TYPE_LABELS[attackType]
            : attackType}
        </Dd>
      </>
    );
  };

  const renderAtonementRow = (row: MonsterElementalRow): ReactNode => (
    <React.Fragment key={row.label}>
      <Dt>
        <span className="inline-flex items-center gap-2">
          {row.label}
          <GeneralToolTip
            label={row.label}
            message={`Reduces incoming ${row.element_name} elemental damage. Matching ${row.element_name} Penetration lowers the ${row.element_name} Atonement used to resist that attack. Elemental strengths and weaknesses are calculated separately.`}
          />
        </span>
      </Dt>
      <Dd>{formatPercent(row.value)}</Dd>
    </React.Fragment>
  );

  const renderPenetrationRow = (row: MonsterElementalRow): ReactNode => (
    <React.Fragment key={row.label}>
      <Dt>
        <span className="inline-flex items-center gap-2">
          {row.label}
          <GeneralToolTip
            label={row.label}
            message={`Reduces the target's ${row.element_name} Atonement when resolving ${row.element_name} elemental damage.`}
          />
        </span>
      </Dt>
      <Dd>{formatPercent(row.value)}</Dd>
    </React.Fragment>
  );

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Raid / Special
      </h2>
      <Dl>
        {renderFlagRow('Raid Monster', raid.is_raid_monster)}
        {renderFlagRow('Raid Boss', raid.is_raid_boss)}
        {renderSpecialAttackType()}
        {atonementRows.map(renderAtonementRow)}
        {penetrationRows.map(renderPenetrationRow)}
      </Dl>
    </div>
  );
};

export default MonsterRaidSection;
