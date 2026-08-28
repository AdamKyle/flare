import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import UseBatchCraftingApiDefinition from './definitions/use-batch-crafting-api-definition';
import BatchCraftingLogsPage from '../definitions/batch-crafting-logs-page-definition';
import {
  ActiveBatchCrafter,
  BatchCraftingChartPoint,
  BatchCraftingFilters,
  BatchCraftingRunRow,
  BatchCraftingSummary,
  Paginated,
} from '../definitions/batch-crafting-monitoring-definition';
import { BatchCraftingApiUrls } from '../enums/batch-crafting-api-urls';

export const useBatchCraftingApi = (): UseBatchCraftingApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchBatchCraftingActive = useCallback(async (): Promise<
    ActiveBatchCrafter[]
  > => {
    return apiHandler.get<ActiveBatchCrafter[], Record<string, never>>(
      getUrl(BatchCraftingApiUrls.ACTIVE)
    );
  }, [apiHandler, getUrl]);

  const fetchBatchCraftingRuns = useCallback(
    async (
      filters: BatchCraftingFilters,
      page: number
    ): Promise<Paginated<BatchCraftingRunRow>> => {
      return apiHandler.get<
        Paginated<BatchCraftingRunRow>,
        BatchCraftingFilters & { page: number }
      >(getUrl(BatchCraftingApiUrls.RUNS), { params: { ...filters, page } });
    },
    [apiHandler, getUrl]
  );

  const fetchBatchCraftingSummary = useCallback(
    async (days: string): Promise<BatchCraftingSummary> => {
      return apiHandler.get<BatchCraftingSummary, { days: string }>(
        getUrl(BatchCraftingApiUrls.SUMMARY),
        { params: { days } }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchBatchCraftingChart = useCallback(
    async (days: string): Promise<BatchCraftingChartPoint[]> => {
      return apiHandler.get<BatchCraftingChartPoint[], { days: string }>(
        getUrl(BatchCraftingApiUrls.CHART),
        { params: { days } }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchBatchCraftingLogs = useCallback(
    async (page: number, severity: string): Promise<BatchCraftingLogsPage> => {
      return apiHandler.get<
        BatchCraftingLogsPage,
        { file: string; page: number; severity: string }
      >(getUrl(BatchCraftingApiUrls.LOG_ENTRIES), {
        params: { file: 'batch_crafting', page, severity },
      });
    },
    [apiHandler, getUrl]
  );

  return {
    fetchBatchCraftingActive,
    fetchBatchCraftingChart,
    fetchBatchCraftingLogs,
    fetchBatchCraftingRuns,
    fetchBatchCraftingSummary,
  };
};
