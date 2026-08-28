import { useEffect, useState } from 'react';

import UseSynchronizedDataTableSearchInputDefinition from './definitions/use-synchronized-data-table-search-input-definition';

export const useSynchronizedDataTableSearchInput = (
  searchValue: string
): UseSynchronizedDataTableSearchInputDefinition => {
  const [searchInputValue, setSearchInputValue] = useState(searchValue);

  useEffect(() => {
    setSearchInputValue(searchValue);
  }, [searchValue]);

  return {
    search_input_value: searchInputValue,
    set_search_input_value: setSearchInputValue,
  };
};
