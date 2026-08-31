import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseGameMapRelatedMonstersDefinition from './definitions/use-game-map-related-monsters-definition';
import GameMapRelatedMonsterDefinition from '../definitions/game-map-related-monster-definition';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

const PER_PAGE = 10;

export const useGameMapRelatedMonsters = (
  gameMapId: number
): UseGameMapRelatedMonstersDefinition => {
  const paginated = UsePaginatedApiHandler<GameMapRelatedMonsterDefinition>(
    {
      url: GameMapApiUrls.RELATED_MONSTERS,
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
