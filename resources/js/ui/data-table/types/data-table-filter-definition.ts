import DataTableFilterOptionDefinition from 'ui/data-table/types/data-table-filter-option-definition';

export default interface DataTableFilterDefinition {
  key: string;
  label: string;
  options: DataTableFilterOptionDefinition[];
  value: string | number | boolean | null;
  on_change: (value: string | number | boolean | null) => void;
  on_clear: () => void;
}
