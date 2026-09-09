import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseMapGemRollsDefinition from './definitions/use-map-gem-rolls-definition';
import UseMapGemRollsParams from './definitions/use-map-gem-rolls-params';
import AdminRolledGemDefinition from '../../../shared/gems/api/definitions/admin-rolled-gem-definition';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

export const useMapGemRolls = ({
  map_gem_id: mapGemId,
}: UseMapGemRollsParams): UseMapGemRollsDefinition => {
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
      url: MapGemApiUrls.ROLLS,
      urlParams: { gameMapGemParamter: mapGemId },
      enabled: mapGemId > 0,
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
