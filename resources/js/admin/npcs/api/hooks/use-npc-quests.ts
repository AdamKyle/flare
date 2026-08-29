import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseNpcQuestsDefinition from './definitions/use-npc-quests-definition';
import NpcQuestDefinition from '../definitions/npc-quest-definition';
import { NpcApiUrls } from '../enums/npc-api-urls';

const PER_PAGE = 10;

export const useNpcQuests = (npcId: number): UseNpcQuestsDefinition => {
  const paginated = UsePaginatedApiHandler<NpcQuestDefinition>(
    {
      url: NpcApiUrls.QUESTS,
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
