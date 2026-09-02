import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GameMapRelatedLocationsSidePeekProps from './types/game-map-related-locations-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import {
  isLocationType,
  LOCATION_TYPE_LABELS,
} from '../../../locations/enums/location-type';
import GameMapRelatedLocationDefinition from '../../api/definitions/game-map-related-location-definition';
import { useGameMapRelatedLocations } from '../../api/hooks/use-game-map-related-locations';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Bounded, append-paginated browser for the Locations on a Game Map,
 * opened from the Game Map's "Related Game Data" hub. Each result opens
 * the canonical Location detail inside a `StackedCard` while preserving
 * this relationship browser underneath (mounted, with its scroll position
 * intact) rather than replacing it through the global SidePeek emitter.
 */
const GameMapRelatedLocationsSidePeek = ({
  game_map_id: gameMapId,
}: GameMapRelatedLocationsSidePeekProps): ReactNode => {
  const locations = useGameMapRelatedLocations(gameMapId);
  const [selectedLocationId, setSelectedLocationId] = useState<number | null>(
    null
  );

  const handleOpenLocation = (id: number): void => {
    setSelectedLocationId(id);
  };

  const handleCloseLocation = (): void => {
    setSelectedLocationId(null);
  };

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const target = event.currentTarget;
    const nearBottom =
      target.scrollHeight - target.scrollTop - target.clientHeight < 100;

    if (nearBottom) {
      locations.on_end_reached();
    }
  };

  const renderLocationType = (
    location: GameMapRelatedLocationDefinition
  ): string | null => {
    if (location.type === null) {
      return null;
    }

    return isLocationType(location.type)
      ? LOCATION_TYPE_LABELS[location.type]
      : String(location.type);
  };

  const renderRow = (location: GameMapRelatedLocationDefinition): ReactNode => (
    <button
      key={location.id}
      type="button"
      onClick={() => handleOpenLocation(location.id)}
      aria-label={`Open Location details for ${location.name}`}
      className="border-glacier-200 dark:border-glacier-800 bg-glacier-50 dark:bg-glacier-900/40 hover:bg-glacier-100 dark:hover:bg-glacier-900 focus-visible:ring-danube-400 w-full rounded-lg border p-3 text-left shadow-sm focus:outline-none focus-visible:ring-2"
    >
      <p className="text-glacier-900 dark:text-glacier-100 font-medium">
        {location.name}
      </p>
      <p className="text-glacier-600 dark:text-glacier-400 text-xs">
        {[renderLocationType(location), `X ${location.x}, Y ${location.y}`]
          .filter(Boolean)
          .join(' · ')}
      </p>
    </button>
  );

  const renderSelectedLocation = (): ReactNode => {
    if (selectedLocationId === null) {
      return null;
    }

    const AdminLocationDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_LOCATION_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseLocation}
        aria_label="Location Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <AdminLocationDetail
          is_open
          title="Location Details"
          location_id={selectedLocationId}
        />
      </StackedCard>
    );
  };

  const renderContent = (): ReactNode => {
    if (locations.loading) {
      return <InfiniteLoader />;
    }

    if (locations.error) {
      return (
        <div className="px-4 py-3">
          <ApiErrorAlert
            apiError={locations.error.message ?? 'Unable to load Locations.'}
          />
        </div>
      );
    }

    if (locations.data.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 px-4 py-3 text-sm">
          This Game Map has no Locations.
        </p>
      );
    }

    return (
      <div className="min-h-0 flex-1 px-2 py-2">
        <InfiniteScroll
          handle_scroll={handleScroll}
          height_class="h-full min-h-0"
        >
          <div className="flex flex-col gap-2">
            {locations.data.map(renderRow)}
            {locations.is_loading_more && <InfiniteLoader />}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      {renderContent()}
      {renderSelectedLocation()}
    </div>
  );
};

export default GameMapRelatedLocationsSidePeek;
