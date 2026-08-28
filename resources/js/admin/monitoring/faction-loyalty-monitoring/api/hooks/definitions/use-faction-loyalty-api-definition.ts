import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import {
  ActiveFactionLoyaltyRunner,
  FactionLoyaltyChartPoint,
  FactionLoyaltyFilters,
  FactionLoyaltyRunRow,
  FactionLoyaltySummary,
} from '../../definitions/faction-loyalty-monitoring-definition';

export default interface UseFactionLoyaltyApiDefinition {
  fetchFactionLoyaltyActive: () => Promise<ActiveFactionLoyaltyRunner[]>;
  fetchFactionLoyaltyRuns: (
    filters: FactionLoyaltyFilters,
    page: number
  ) => Promise<PaginatedApiResponseDefinition<FactionLoyaltyRunRow[]>>;
  fetchFactionLoyaltySummary: (days: string) => Promise<FactionLoyaltySummary>;
  fetchFactionLoyaltyChart: (
    days: string
  ) => Promise<FactionLoyaltyChartPoint[]>;
}
