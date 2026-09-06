import React, { ReactNode } from 'react';

import LocationGemBulkRollResultSidePeekProps from './types/location-gem-bulk-roll-result-side-peek-props';
import AdminRolledGemCard from '../../../shared/gems/components/admin-rolled-gem-card';
import { LOCATION_GEM_RANGE_DISPLAY_GROUPS } from '../../definitions/location-gem-range-display';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

/**
 * Displays the actual result of a Location Gem Roll All bulk action: the
 * real randomized values rolled for every profile, as a scrollable feed of
 * Gem roll cards rather than a crushed profile-name list.
 */
const LocationGemBulkRollResultSidePeek = ({
  result,
}: LocationGemBulkRollResultSidePeekProps): ReactNode => (
  <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
    <div className="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:px-5">
      <Card>
        <Dl>
          <Dt>Rolled</Dt>
          <Dd>{result.rolled_count}</Dd>
        </Dl>
      </Card>

      {result.rolled.map((item) => (
        <AdminRolledGemCard
          key={item.profile_id}
          roll={item.rolled_gem}
          source_label="Location"
          source_name={item.source_name}
          profile_name={item.profile_name}
          display_groups={LOCATION_GEM_RANGE_DISPLAY_GROUPS}
        />
      ))}
    </div>
  </div>
);

export default LocationGemBulkRollResultSidePeek;
