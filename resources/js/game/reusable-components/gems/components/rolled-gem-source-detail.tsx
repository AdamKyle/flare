import React, { Fragment, ReactNode } from 'react';

import RolledGemStats from './rolled-gem-stats';
import RolledGemSourceDetailProps from '../types/rolled-gem-source-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

/**
 * Factual detail body for one resolved Area Gem source: its identity,
 * effective multipliers, and the concrete rolled Gem's non-zero stats.
 */
const RolledGemSourceDetail = ({
  source,
  display_groups: displayGroups,
}: RolledGemSourceDetailProps): ReactNode => {
  const isMapGem = source.type === 'map_gem';

  return (
    <div className="flex flex-col gap-3">
      <Dl>
        <Dt>Source</Dt>
        <Dd>{isMapGem ? 'Map Gem' : 'Location Gem'}</Dd>
        <Dt>Profile</Dt>
        <Dd>{source.profile_name}</Dd>
        {source.game_map_name && (
          <Fragment>
            <Dt>Game Map</Dt>
            <Dd>{source.game_map_name}</Dd>
          </Fragment>
        )}
        {source.location_name && (
          <Fragment>
            <Dt>Location</Dt>
            <Dd>{source.location_name}</Dd>
          </Fragment>
        )}
        <Dt>Monster Multiplier</Dt>
        <Dd>×{source.monster_multiplier}</Dd>
        <Dt>Reward Multiplier</Dt>
        <Dd>×{source.reward_multiplier}</Dd>
        {source.reduction_multiplier !== null && (
          <Fragment>
            <Dt>Character Power Reduction Multiplier</Dt>
            <Dd>×{source.reduction_multiplier}</Dd>
          </Fragment>
        )}
      </Dl>
      <Separator additional_css="my-0" />
      <RolledGemStats roll={source.rolled_gem} display_groups={displayGroups} />
    </div>
  );
};

export default RolledGemSourceDetail;
