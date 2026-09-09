import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseLocationGemRollsDefinition from './definitions/use-location-gem-rolls-definition';
import UseLocationGemRollsParams from './definitions/use-location-gem-rolls-params';
import AdminRolledGemDefinition from '../../../shared/gems/api/definitions/admin-rolled-gem-definition';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

export const useLocationGemRolls = ({
  location_gem_id: locationGemId,
}: UseLocationGemRollsParams): UseLocationGemRollsDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    error,
    canLoadMore,
    onEndReached,
    setRefresh,
  } = UsePaginatedApiHandler<AdminRolledGemDefinition>(
    {
      url: LocationGemApiUrls.ROLLS,
      urlParams: { gameLocationGemParamter: locationGemId },
      enabled: locationGemId > 0,
    },
    10
  );

  const refresh = (): void => setRefresh((previousValue) => !previousValue);

  return {
    rolls: data,
    loading,
    is_loading_more: isLoadingMore,
    error,
    has_more: canLoadMore,
    load_next: onEndReached,
    refresh,
  };
};
