import DataTableActionDefinition from 'ui/data-table/types/data-table-action-definition';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';
import DataTableFilterDefinition from 'ui/data-table/types/data-table-filter-definition';

export default interface DataTableProps<TRow> {
  id_prefix: string;
  caption: string;
  rows: TRow[];
  row_id: (row: TRow) => string | number;
  columns: DataTableColumnDefinition<TRow>[];
  loading: boolean;
  error: string | null;
  empty_message: string;
  search_label: string;
  search_value: string;
  on_search_change: (value: string) => void;
  filters?: DataTableFilterDefinition[];
  current_page: number;
  total_pages: number;
  total_records: number;
  on_page_change: (page: number) => void;
  sort_key?: string;
  sort_direction?: 'asc' | 'desc';
  on_sort_change?: (sort_key: string) => void;
  on_row_activate?: (row: TRow) => void;
  row_actions?: DataTableActionDefinition<TRow>[];
}
