import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseGameMapRelatedQuestItemsDefinition from './definitions/use-game-map-related-quest-items-definition';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

const PER_PAGE = 10;

export const useGameMapRelatedQuestItems = (
  gameMapId: number
): UseGameMapRelatedQuestItemsDefinition => {
  const paginated =
    UsePaginatedApiHandler<AdminQuestItemPresentationDefinition>(
      {
        url: GameMapApiUrls.RELATED_QUEST_ITEMS,
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
