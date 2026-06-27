import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import {
  ActiveDelveRunner,
  DelveChartPoint,
  DelveFilters,
  DelveRunRow,
  DelveSummary,
} from '../types/delve-monitoring';

const base = '/admin/monitoring/delve';

export const useDelveApi = () => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchDelveActive = useCallback(async (): Promise<
    ActiveDelveRunner[]
  > => {
    return apiHandler.get<ActiveDelveRunner[], Record<string, never>>(
      getUrl(`${base}/active`)
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
      >(getUrl(`${base}/runs`), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchDelveSummary = useCallback(
    async (days: string): Promise<DelveSummary> => {
      return apiHandler.get<DelveSummary, { days: string }>(
        getUrl(`${base}/summary`),
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
        getUrl(`${base}/chart`),
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
