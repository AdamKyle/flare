import { ReactNode } from 'react';

export default interface DataTableColumnDefinition<TRow> {
  key: string;
  header: string;
  value: (row: TRow) => ReactNode;
  sortable?: boolean;
  sort_key?: string;
  class_name?: string;
  header_class_name?: string;
  minimum_width?: string;
}
