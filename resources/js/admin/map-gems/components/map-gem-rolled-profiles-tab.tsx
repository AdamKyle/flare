import React, { ReactNode } from 'react';

import MapGemRolledProfilesTabProps from './types/map-gem-rolled-profiles-tab-props';
import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminGemRollSummaryCard from '../../shared/gems/components/admin-gem-roll-summary-card';
import { useMapGemRolls } from '../api/hooks/use-map-gem-rolls';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MapGemRolledProfilesTab = ({
  map_gem_id: mapGemId,
}: MapGemRolledProfilesTabProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const {
    rolls,
    loading,
    error,
    has_more: hasMore,
    load_next: loadNext,
    refresh,
  } = useMapGemRolls({ map_gem_id: mapGemId });

  const handleOpenRoll = (roll: (typeof rolls)[number]): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_ROLL_DETAIL,
      {
        is_open: true,
        title: `Roll #${roll.roll_number}`,
        allow_clicking_outside: true,
        map_gem_id: mapGemId,
        roll,
        on_activated: refresh,
      }
    );
  };

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;

    if (scrollTop + clientHeight < scrollHeight - 10) {
      return;
    }

    loadNext();
  };

  if (loading && rolls.length === 0) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>;
  }

  if (rolls.length === 0) {
    return (
      <p className="text-gray-700 dark:text-gray-300">
        No Gem has been rolled for this profile yet.
      </p>
    );
  }

  return (
    <InfiniteScroll
      height_class="h-auto max-h-[70vh]"
      handle_scroll={handleScroll}
    >
      <div className="flex flex-col gap-2">
        {rolls.map((roll) => (
          <AdminGemRollSummaryCard
            key={roll.id}
            roll={roll}
            on_click={() => handleOpenRoll(roll)}
          />
        ))}
        {hasMore && <InfiniteLoader />}
      </div>
    </InfiniteScroll>
  );
};

export default MapGemRolledProfilesTab;
