import { RefObject, useEffect } from 'react';

export const useGameMapCanvasFocus = (
  focusToken: number,
  gridRef: RefObject<HTMLDivElement | null>
): void => {
  useEffect(() => {
    if (focusToken === 0) {
      return;
    }

    gridRef.current?.focus({ preventScroll: true });
  }, [focusToken, gridRef]);
};
