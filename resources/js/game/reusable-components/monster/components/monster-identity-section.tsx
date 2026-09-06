import React, { ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
  formatRangeWithCommas,
} from '../../../util/format-number';
import FactualLink from '../../quest-item/partials/factual-link';
import { isLocationType, LOCATION_TYPE_LABELS } from '../enums/location-type';
import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

/**
 * Monster name is already the outer `MonsterDetail` heading; this section
 * never repeats it as a separate factual row.
 */
const MonsterIdentitySection = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const { identity } = monster;

  const renderLocationTypeLabel = (value: number): ReactNode =>
    isLocationType(value) ? LOCATION_TYPE_LABELS[value] : value;

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Identity &amp; Placement
      </h2>
      <Dl>
        <Dt>Damage Stat</Dt>
        <Dd>{identity.damage_stat}</Dd>
        {identity.game_map && (
          <>
            <Dt>Game Map</Dt>
            <Dd>
              <FactualLink
                id={identity.game_map.id}
                label={identity.game_map.name}
                on_click={navigation?.on_open_map}
              />
            </Dd>
          </>
        )}
        {identity.max_level > 0 && (
          <>
            <Dt>Max Level</Dt>
            <Dd>{formatNumberWithCommas(identity.max_level)}</Dd>
          </>
        )}
        {identity.xp > 0 && (
          <>
            <Dt>XP</Dt>
            <Dd>{formatNumberWithCommas(identity.xp)}</Dd>
          </>
        )}
        {identity.gold > 0 && (
          <>
            <Dt>Gold</Dt>
            <Dd>{formatNumberWithCommas(identity.gold)}</Dd>
          </>
        )}
        <Dt>Health Range</Dt>
        <Dd>{formatRangeWithCommas(identity.health_range)}</Dd>
        <Dt>Attack Range</Dt>
        <Dd>{formatRangeWithCommas(identity.attack_range)}</Dd>
        {identity.drop_check > 0 && (
          <>
            <Dt>Drop Check</Dt>
            <Dd>{formatPercent(identity.drop_check)}</Dd>
          </>
        )}
        {identity.only_for_location_type !== null && (
          <>
            <Dt>Only For Location Type</Dt>
            <Dd>{renderLocationTypeLabel(identity.only_for_location_type)}</Dd>
          </>
        )}
      </Dl>
    </Card>
  );
};

export default MonsterIdentitySection;
