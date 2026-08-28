import DataTableFilterDefinition from './data-table-filter-definition';

export default interface DataTableFiltersProps {
  id_prefix: string;
  search_label: string;
  search_value: string;
  on_search_change: (value: string) => void;
  filters?: DataTableFilterDefinition[];
}
