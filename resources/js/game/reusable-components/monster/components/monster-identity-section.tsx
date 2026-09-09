import React, { ReactNode } from 'react';

import { formatNumberWithCommas } from '../../../util/format-number';
import FactualLink from '../../quest-item/partials/factual-link';
import { isLocationType, LOCATION_TYPE_LABELS } from '../enums/location-type';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterIdentitySection = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const { identity } = monster;

  const renderLocationTypeLabel = (value: number): ReactNode =>
    isLocationType(value) ? LOCATION_TYPE_LABELS[value] : value;

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
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
        {identity.only_for_location_type !== null && (
          <>
            <Dt>Only For Location Type</Dt>
            <Dd>{renderLocationTypeLabel(identity.only_for_location_type)}</Dd>
          </>
        )}
      </Dl>
    </div>
  );
};

export default MonsterIdentitySection;
