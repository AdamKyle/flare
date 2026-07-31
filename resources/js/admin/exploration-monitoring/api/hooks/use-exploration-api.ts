import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseExplorationApiDefinition from './definitions/use-exploration-api-definition';
import {
  ActiveExplorer,
  ExplorationChartPoint,
  ExplorationFilters,
  ExplorationLogRow,
  ExplorationSummary,
} from '../definitions/exploration-monitoring-definition';
import { ExplorationApiUrls } from '../enums/exploration-api-urls';

export const useExplorationApi = (): UseExplorationApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchExplorationActive = useCallback(async (): Promise<
    ActiveExplorer[]
  > => {
    return apiHandler.get<ActiveExplorer[], Record<string, never>>(
      getUrl(ExplorationApiUrls.ACTIVE)
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
      >(getUrl(ExplorationApiUrls.LOGS), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchExplorationSummary = useCallback(
    async (days: string): Promise<ExplorationSummary> => {
      return apiHandler.get<ExplorationSummary, { days: string }>(
        getUrl(ExplorationApiUrls.SUMMARY),
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
        getUrl(ExplorationApiUrls.CHART),
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
