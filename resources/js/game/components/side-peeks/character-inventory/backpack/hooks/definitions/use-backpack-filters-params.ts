import BackpackFilterDefinition from '../../types/backpack-filter-definition';

export default interface UseBackpackFiltersParams {
  initial_filters?: BackpackFilterDefinition;
  setFilters: (filters: BackpackFilterDefinition) => void;
}
