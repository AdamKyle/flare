import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { useCallback } from 'react';

import {
  CharacterDetailResponse,
  CharacterRow,
  ChartPoint,
  ChartsResponse,
  RepairSummary,
  RequestFiltersType,
  RewardRequest,
  StaleQueue,
  Summary,
} from '../types/reward-queue';

const baseUrl = '/admin/character-reward-queue';

export const useRewardQueueApi = () => {
  const { apiHandler, getUrl } = useApiHandler();

  const fetchRewardQueueSummary = useCallback(async (): Promise<Summary> => {
    return apiHandler.get<Summary, Record<string, never>>(
      getUrl(`${baseUrl}/summary`)
    );
  }, [apiHandler, getUrl]);

  const fetchRewardQueueCharts =
    useCallback(async (): Promise<ChartsResponse> => {
      return apiHandler.get<ChartsResponse, Record<string, never>>(
        getUrl(`${baseUrl}/charts`)
      );
    }, [apiHandler, getUrl]);

  const fetchRewardQueueCharacters = useCallback(
    async (
      page: number
    ): Promise<PaginatedApiResponseDefinition<CharacterRow[]>> => {
      return apiHandler.get<
        PaginatedApiResponseDefinition<CharacterRow[]>,
        { page: number }
      >(getUrl(`${baseUrl}/characters`), {
        params: { page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchRewardQueueRequests = useCallback(
    async (
      filters: RequestFiltersType,
      page: number
    ): Promise<PaginatedApiResponseDefinition<RewardRequest[]>> => {
      return apiHandler.get<
        PaginatedApiResponseDefinition<RewardRequest[]>,
        RequestFiltersType & { page: number }
      >(getUrl(`${baseUrl}/requests`), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchCharacterRewardQueue = useCallback(
    async (
      characterId: number,
      filters: RequestFiltersType,
      page: number
    ): Promise<CharacterDetailResponse> => {
      return apiHandler.get<
        CharacterDetailResponse,
        RequestFiltersType & { page: number }
      >(getUrl(`${baseUrl}/characters/${characterId}`), {
        params: { ...filters, page },
      });
    },
    [apiHandler, getUrl]
  );

  const fetchRewardQueueStatusVolume = useCallback(
    async (days: string): Promise<ChartPoint[]> => {
      return apiHandler.get<ChartPoint[], { days: string }>(
        getUrl(`${baseUrl}/status-breakdown`),
        {
          params: { days },
        }
      );
    },
    [apiHandler, getUrl]
  );

  const fetchStaleRewardQueues = useCallback(async (): Promise<
    StaleQueue[]
  > => {
    return apiHandler.get<StaleQueue[], Record<string, never>>(
      getUrl(`${baseUrl}/stale`)
    );
  }, [apiHandler, getUrl]);

  const repairStaleRewardQueues =
    useCallback(async (): Promise<RepairSummary> => {
      return apiHandler.post<RepairSummary, Record<string, never>, object>(
        getUrl(`${baseUrl}/stale/repair`),
        {}
      );
    }, [apiHandler, getUrl]);

  return {
    fetchRewardQueueSummary,
    fetchRewardQueueCharts,
    fetchRewardQueueCharacters,
    fetchRewardQueueRequests,
    fetchCharacterRewardQueue,
    fetchRewardQueueStatusVolume,
    fetchStaleRewardQueues,
    repairStaleRewardQueues,
  };
};
