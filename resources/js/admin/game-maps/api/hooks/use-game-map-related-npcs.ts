import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseGameMapRelatedNpcsDefinition from './definitions/use-game-map-related-npcs-definition';
import GameMapRelatedNpcDefinition from '../definitions/game-map-related-npc-definition';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

const PER_PAGE = 10;

export const useGameMapRelatedNpcs = (
  gameMapId: number
): UseGameMapRelatedNpcsDefinition => {
  const paginated = UsePaginatedApiHandler<GameMapRelatedNpcDefinition>(
    {
      url: GameMapApiUrls.RELATED_NPCS,
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
