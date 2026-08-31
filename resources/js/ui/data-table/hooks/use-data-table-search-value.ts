import UseDataTableSearchValueDefinition from './definitions/use-data-table-search-value-definition';
import { useDataTableSearchNotification } from './use-data-table-search-notification';
import { useSynchronizedDataTableSearchInput } from './use-synchronized-data-table-search-input';
import { useDebouncedValue } from '../../../utils/hooks/use-debounced-value';
import { DataTableValues } from '../enums/data-table-values';

export const useDataTableSearchValue = (
  searchValue: string,
  onSearchChange: (value: string) => void
): UseDataTableSearchValueDefinition => {
  const { search_input_value, set_search_input_value } =
    useSynchronizedDataTableSearchInput(searchValue);
  const debouncedSearchInputValue = useDebouncedValue(
    search_input_value,
    DataTableValues.SearchDebounceMs
  );

  useDataTableSearchNotification(
    debouncedSearchInputValue,
    searchValue,
    onSearchChange
  );

  return {
    search_input_value,
    set_search_input_value,
  };
};
