import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import {
  ActiveExplorer,
  ExplorationChartPoint,
  ExplorationFilters,
  ExplorationLogRow,
  ExplorationSummary,
} from '../types/exploration-monitoring';

const base = '/admin/monitoring/exploration';

export const useExplorationApi = () => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchExplorationActive = useCallback(async (): Promise<
    ActiveExplorer[]
  > => {
    return apiHandler.get<ActiveExplorer[], Record<string, never>>(
      getUrl(`${base}/active`)
    );
  }, [apiHandler, getUrl]);

  const fetchExplorationLogs = useCallback(
    async (
      filters: ExplorationFilters,
      page: number
    ): Promise<PaginatedApiResponseDefinition<ExplorationLogRow[]>> => {
      return apiHandler.get<
        PaginatedApiResponseDefinition<ExplorationLogRow[]>,
        ExplorationFilters & { page: number }
      >(getUrl(`${base}/logs`), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchExplorationSummary = useCallback(
    async (days: string): Promise<ExplorationSummary> => {
      return apiHandler.get<ExplorationSummary, { days: string }>(
        getUrl(`${base}/summary`),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchExplorationChart = useCallback(
    async (days: string): Promise<ExplorationChartPoint[]> => {
      return apiHandler.get<ExplorationChartPoint[], { days: string }>(
        getUrl(`${base}/chart`),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  return {
    fetchExplorationActive,
    fetchExplorationLogs,
    fetchExplorationSummary,
    fetchExplorationChart,
  };
};
