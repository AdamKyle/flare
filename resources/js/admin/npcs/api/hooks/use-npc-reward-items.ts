import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseNpcRewardItemsDefinition from './definitions/use-npc-reward-items-definition';
import { NpcApiUrls } from '../enums/npc-api-urls';

import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';

const PER_PAGE = 10;

export const useNpcRewardItems = (
  npcId: number
): UseNpcRewardItemsDefinition => {
  const paginated =
    UsePaginatedApiHandler<AdminQuestItemPresentationDefinition>(
      {
        url: NpcApiUrls.REWARD_ITEMS,
        urlParams: { npc: npcId },
        paginationMode: 'replace',
      },
      PER_PAGE
    );

  const refresh = (): void => {
    paginated.setRefresh((previous) => !previous);
  };

  return {
    data: paginated.data,
    loading: paginated.loading,
    error: paginated.error,
    page: paginated.page,
    set_page: paginated.setPage,
    total_pages: paginated.response?.meta.pagination.total_pages ?? 0,
    total_records: paginated.response?.meta.pagination.total ?? 0,
    refresh,
  };
};
