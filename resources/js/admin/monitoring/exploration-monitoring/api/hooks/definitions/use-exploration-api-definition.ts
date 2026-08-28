import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import {
  ActiveExplorer,
  ExplorationChartPoint,
  ExplorationFilters,
  ExplorationLogRow,
  ExplorationSummary,
} from '../../definitions/exploration-monitoring-definition';

export default interface UseExplorationApiDefinition {
  fetchExplorationActive: () => Promise<ActiveExplorer[]>;
  fetchExplorationLogs: (
    filters: ExplorationFilters,
    page: number
  ) => Promise<PaginatedApiResponseDefinition<ExplorationLogRow[]>>;
  fetchExplorationSummary: (days: string) => Promise<ExplorationSummary>;
  fetchExplorationChart: (days: string) => Promise<ExplorationChartPoint[]>;
}
