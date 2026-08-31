import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseLocationQuestItemsDefinition from './definitions/use-location-quest-items-definition';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import { LocationQuestItemsResponseDefinition } from '../definitions/location-quest-items-response-definition';
import { LocationApiUrls } from '../enums/location-api-urls';

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
    drop_mode: paginated.response?.meta.location_drop_mode ?? null,
    on_end_reached: paginated.onEndReached,
    refresh,
  };
};
