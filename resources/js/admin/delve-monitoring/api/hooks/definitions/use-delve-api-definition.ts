import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import {
  ActiveDelveRunner,
  DelveChartPoint,
  DelveFilters,
  DelveRunRow,
  DelveSummary,
} from '../../definitions/delve-monitoring-definition';

export default interface UseDelveApiDefinition {
  fetchDelveActive: () => Promise<ActiveDelveRunner[]>;
  fetchDelveRuns: (
    filters: DelveFilters,
    page: number
  ) => Promise<PaginatedApiResponseDefinition<DelveRunRow[]>>;
  fetchDelveSummary: (days: string) => Promise<DelveSummary>;
  fetchDelveChart: (days: string) => Promise<DelveChartPoint[]>;
}
