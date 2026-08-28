import { useEffect, useState } from 'react';

import { DebounceValues } from './enums/debounce-values';

export const useDebouncedValue = <T>(
  value: T,
  delayMs: number = DebounceValues.Default
): T => {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const timeoutId = window.setTimeout(() => {
      setDebouncedValue(value);
    }, delayMs);

    return () => {
      window.clearTimeout(timeoutId);
    };
  }, [value, delayMs]);

  return debouncedValue;
};
