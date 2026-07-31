import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseFactionLoyaltyApiDefinition from './definitions/use-faction-loyalty-api-definition';
import {
  ActiveFactionLoyaltyRunner,
  FactionLoyaltyChartPoint,
  FactionLoyaltyFilters,
  FactionLoyaltyRunRow,
  FactionLoyaltySummary,
} from '../definitions/faction-loyalty-monitoring-definition';
import { FactionLoyaltyApiUrls } from '../enums/faction-loyalty-api-urls';

export const useFactionLoyaltyApi = (): UseFactionLoyaltyApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchFactionLoyaltyActive = useCallback(async (): Promise<
    ActiveFactionLoyaltyRunner[]
  > => {
    return apiHandler.get<ActiveFactionLoyaltyRunner[], Record<string, never>>(
      getUrl(FactionLoyaltyApiUrls.ACTIVE)
    );
  }, [apiHandler, getUrl]);

  const fetchFactionLoyaltyRuns = useCallback(
    async (
      filters: FactionLoyaltyFilters,
      page: number
    ): Promise<PaginatedApiResponseDefinition<FactionLoyaltyRunRow[]>> => {
      return apiHandler.get<
        PaginatedApiResponseDefinition<FactionLoyaltyRunRow[]>,
        FactionLoyaltyFilters & { page: number }
      >(getUrl(FactionLoyaltyApiUrls.RUNS), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchFactionLoyaltySummary = useCallback(
    async (days: string): Promise<FactionLoyaltySummary> => {
      return apiHandler.get<FactionLoyaltySummary, { days: string }>(
        getUrl(FactionLoyaltyApiUrls.SUMMARY),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchFactionLoyaltyChart = useCallback(
    async (days: string): Promise<FactionLoyaltyChartPoint[]> => {
      return apiHandler.get<FactionLoyaltyChartPoint[], { days: string }>(
        getUrl(FactionLoyaltyApiUrls.CHART),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  return {
    fetchFactionLoyaltyActive,
    fetchFactionLoyaltyRuns,
    fetchFactionLoyaltySummary,
    fetchFactionLoyaltyChart,
  };
};
