import { useEffect } from 'react';

export const useDataTableSearchNotification = (
  debouncedSearchInputValue: string,
  searchValue: string,
  onSearchChange: (value: string) => void
): void => {
  useEffect(() => {
    if (debouncedSearchInputValue === searchValue) {
      return;
    }

    onSearchChange(debouncedSearchInputValue);
  }, [debouncedSearchInputValue, searchValue, onSearchChange]);
};
