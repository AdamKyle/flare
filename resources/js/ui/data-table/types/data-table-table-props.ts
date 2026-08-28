import DataTableActionDefinition from 'ui/data-table/types/data-table-action-definition';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export default interface DataTableTableProps<TRow> {
  caption: string;
  rows: TRow[];
  row_id: (row: TRow) => string | number;
  columns: DataTableColumnDefinition<TRow>[];
  empty_message: string;
  sort_key?: string;
  sort_direction?: 'asc' | 'desc';
  on_sort_change?: (sort_key: string) => void;
  on_row_activate?: (row: TRow) => void;
  row_actions?: DataTableActionDefinition<TRow>[];
}
