import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseGameMapRelatedQuestsDefinition from './definitions/use-game-map-related-quests-definition';
import GameMapRelatedQuestDefinition from '../definitions/game-map-related-quest-definition';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

const PER_PAGE = 10;

export const useGameMapRelatedQuests = (
  gameMapId: number
): UseGameMapRelatedQuestsDefinition => {
  const paginated = UsePaginatedApiHandler<GameMapRelatedQuestDefinition>(
    {
      url: GameMapApiUrls.RELATED_QUESTS,
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
