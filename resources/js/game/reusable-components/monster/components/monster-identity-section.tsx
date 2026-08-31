import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import { isLocationType, LOCATION_TYPE_LABELS } from '../enums/location-type';
import MonsterDetailProps from '../types/monster-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterIdentitySection = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const { identity } = monster;

  const renderLocationType = (): ReactNode => {
    if (identity.only_for_location_type === null) {
      return 'None';
    }

    return isLocationType(identity.only_for_location_type)
      ? LOCATION_TYPE_LABELS[identity.only_for_location_type]
      : identity.only_for_location_type;
  };

  const renderGameMap = (): ReactNode => {
    if (!identity.game_map) {
      return 'None';
    }

    return (
      <FactualLink
        id={identity.game_map.id}
        label={identity.game_map.name}
        on_click={navigation?.on_open_map}
      />
    );
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Identity &amp; Placement
      </h2>
      <Dl>
        <Dt>Name</Dt>
        <Dd>{identity.name}</Dd>
        <Dt>Damage Stat</Dt>
        <Dd>{identity.damage_stat}</Dd>
        <Dt>Game Map</Dt>
        <Dd>{renderGameMap()}</Dd>
        <Dt>Max Level</Dt>
        <Dd>{identity.max_level}</Dd>
        <Dt>XP</Dt>
        <Dd>{identity.xp}</Dd>
        <Dt>Gold</Dt>
        <Dd>{identity.gold}</Dd>
        <Dt>Health Range</Dt>
        <Dd>{identity.health_range}</Dd>
        <Dt>Attack Range</Dt>
        <Dd>{identity.attack_range}</Dd>
        <Dt>Drop Check</Dt>
        <Dd>{identity.drop_check}</Dd>
        <Dt>Only For Location Type</Dt>
        <Dd>{renderLocationType()}</Dd>
      </Dl>
    </Card>
  );
};

export default MonsterIdentitySection;
