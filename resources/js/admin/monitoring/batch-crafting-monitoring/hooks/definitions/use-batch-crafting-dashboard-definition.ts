import BatchCraftingLogsPage from '../../api/definitions/batch-crafting-logs-page-definition';
import {
  ActiveBatchCrafter,
  BatchCraftingFilters,
  BatchCraftingRunRow,
  BatchCraftingSummary,
  Paginated,
} from '../../api/definitions/batch-crafting-monitoring-definition';

export interface ChartPoint {
  period: string;
  runs: number;
  crafted: number;
  failed: number;
}

export interface ChartSeriesDefinition {
  key: string;
  label: string;
  color: string;
}

export default interface UseBatchCraftingDashboardDefinition {
  loading: boolean;
  error: string | null;
  days: string;
  set_days: (days: string) => void;
  summary: BatchCraftingSummary;
  chart_points: ChartPoint[];
  chart_series: ChartSeriesDefinition[];
  active: ActiveBatchCrafter[];
  runs: Paginated<BatchCraftingRunRow>;
  filters: BatchCraftingFilters;
  update_filters: (filters: BatchCraftingFilters) => void;
  apply_table_filter: (filters: Partial<BatchCraftingFilters>) => void;
  page: number;
  set_page: (page: number) => void;
  logs: BatchCraftingLogsPage;
  log_page: number;
  set_log_page: (page: number) => void;
  log_severity: string;
  update_log_severity: (severity: string) => void;
}
