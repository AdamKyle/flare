import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

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
} from '../../definitions/reward-queue-definition';

export default interface UseRewardQueueApiDefinition {
  fetchRewardQueueSummary: () => Promise<Summary>;
  fetchRewardQueueCharts: () => Promise<ChartsResponse>;
  fetchRewardQueueCharacters: (
    page: number
  ) => Promise<PaginatedApiResponseDefinition<CharacterRow[]>>;
  fetchRewardQueueRequests: (
    filters: RequestFiltersType,
    page: number
  ) => Promise<PaginatedApiResponseDefinition<RewardRequest[]>>;
  fetchCharacterRewardQueue: (
    characterId: number,
    filters: RequestFiltersType,
    page: number
  ) => Promise<CharacterDetailResponse>;
  fetchRewardQueueStatusVolume: (days: string) => Promise<ChartPoint[]>;
  fetchStaleRewardQueues: () => Promise<StaleQueue[]>;
  repairStaleRewardQueues: () => Promise<RepairSummary>;
}
