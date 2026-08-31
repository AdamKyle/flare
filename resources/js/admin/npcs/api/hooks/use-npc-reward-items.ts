import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseNpcRewardItemsDefinition from './definitions/use-npc-reward-items-definition';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import { NpcApiUrls } from '../enums/npc-api-urls';

const PER_PAGE = 10;

export const useNpcRewardItems = (
  npcId: number
): UseNpcRewardItemsDefinition => {
  const paginated =
    UsePaginatedApiHandler<AdminQuestItemPresentationDefinition>(
      {
        url: NpcApiUrls.REWARD_ITEMS,
        urlParams: { npc: npcId },
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
