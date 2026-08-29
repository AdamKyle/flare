import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseLocationQuestItemsDefinition from './definitions/use-location-quest-items-definition';
import { LocationApiUrls } from '../enums/location-api-urls';
import { LocationQuestItemsResponseDefinition } from '../definitions/location-quest-items-response-definition';

import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';

const PER_PAGE = 10;

export const useLocationQuestItems = (
  locationId: number
): UseLocationQuestItemsDefinition => {
  const paginated = UsePaginatedApiHandler<
    AdminQuestItemPresentationDefinition,
    Record<string, unknown>,
    LocationQuestItemsResponseDefinition
  >(
    {
      url: LocationApiUrls.QUEST_ITEMS,
      urlParams: { location: locationId },
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
    drop_mode: paginated.response?.meta.location_drop_mode ?? null,
    page: paginated.page,
    set_page: paginated.setPage,
    total_pages: paginated.response?.meta.pagination.total_pages ?? 0,
    total_records: paginated.response?.meta.pagination.total ?? 0,
    refresh,
  };
};
