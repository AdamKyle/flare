import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseDelveApiDefinition from './definitions/use-delve-api-definition';
import {
  ActiveDelveRunner,
  DelveChartPoint,
  DelveFilters,
  DelveRunRow,
  DelveSummary,
} from '../definitions/delve-monitoring-definition';
import { DelveApiUrls } from '../enums/delve-api-urls';

export const useDelveApi = (): UseDelveApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchDelveActive = useCallback(async (): Promise<
    ActiveDelveRunner[]
  > => {
    return apiHandler.get<ActiveDelveRunner[], Record<string, never>>(
      getUrl(DelveApiUrls.ACTIVE)
    );
  }, [apiHandler, getUrl]);

  const fetchDelveRuns = useCallback(
    async (
      filters: DelveFilters,
      page: number
    ): Promise<PaginatedApiResponseDefinition<DelveRunRow[]>> => {
      return apiHandler.get<
        PaginatedApiResponseDefinition<DelveRunRow[]>,
        DelveFilters & { page: number }
      >(getUrl(DelveApiUrls.RUNS), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchDelveSummary = useCallback(
    async (days: string): Promise<DelveSummary> => {
      return apiHandler.get<DelveSummary, { days: string }>(
        getUrl(DelveApiUrls.SUMMARY),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchDelveChart = useCallback(
    async (days: string): Promise<DelveChartPoint[]> => {
      return apiHandler.get<DelveChartPoint[], { days: string }>(
        getUrl(DelveApiUrls.CHART),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  return {
    fetchDelveActive,
    fetchDelveRuns,
    fetchDelveSummary,
    fetchDelveChart,
  };
};
