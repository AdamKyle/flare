import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import {
  ActiveFactionLoyaltyRunner,
  FactionLoyaltyChartPoint,
  FactionLoyaltyFilters,
  FactionLoyaltyRunRow,
  FactionLoyaltySummary,
} from '../types/faction-loyalty-monitoring';

const base = '/admin/monitoring/faction-loyalty';

export const useFactionLoyaltyApi = () => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchFactionLoyaltyActive = useCallback(async (): Promise<
    ActiveFactionLoyaltyRunner[]
  > => {
    return apiHandler.get<ActiveFactionLoyaltyRunner[], Record<string, never>>(
      getUrl(`${base}/active`)
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
      >(getUrl(`${base}/runs`), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchFactionLoyaltySummary = useCallback(
    async (days: string): Promise<FactionLoyaltySummary> => {
      return apiHandler.get<FactionLoyaltySummary, { days: string }>(
        getUrl(`${base}/summary`),
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
        getUrl(`${base}/chart`),
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
