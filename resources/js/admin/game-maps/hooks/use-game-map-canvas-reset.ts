import { useEffect } from 'react';

export const useGameMapCanvasReset = (
  resetToken: number,
  resetTranslate: () => void
): void => {
  useEffect(() => {
    resetTranslate();
  }, [resetToken, resetTranslate]);
};
