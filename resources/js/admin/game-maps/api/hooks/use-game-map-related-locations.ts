import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseGameMapRelatedLocationsDefinition from './definitions/use-game-map-related-locations-definition';
import GameMapRelatedLocationDefinition from '../definitions/game-map-related-location-definition';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

const PER_PAGE = 10;

export const useGameMapRelatedLocations = (
  gameMapId: number
): UseGameMapRelatedLocationsDefinition => {
  const paginated = UsePaginatedApiHandler<GameMapRelatedLocationDefinition>(
    {
      url: GameMapApiUrls.RELATED_LOCATIONS,
      urlParams: { gameMap: gameMapId },
    },
    PER_PAGE
  );

  const refresh = (): void => {
    paginated.setRefresh((previous) => !previous);
  };

  return {
    data: paginated.data,
    loading: paginated.loading,
    is_loading_more: paginated.isLoadingMore,
    error: paginated.error,
    on_end_reached: paginated.onEndReached,
    refresh,
  };
};
