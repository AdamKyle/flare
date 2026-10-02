import { useCallback, useEffect, useRef } from 'react';

import UseGuideTargetClickAdvancementDefinition from './definitions/use-guide-target-click-advancement-definition';

export const useGuideTargetClickAdvancement =
  (): UseGuideTargetClickAdvancementDefinition => {
    const targetRef = useRef<HTMLElement | null>(null);
    const clickHandlerRef = useRef<(() => void) | null>(null);

    const unbindTargetClick = useCallback((): void => {
      if (targetRef.current && clickHandlerRef.current) {
        targetRef.current.removeEventListener('click', clickHandlerRef.current);
      }

      targetRef.current = null;
      clickHandlerRef.current = null;
    }, []);

    const bindTargetClick = useCallback(
      (target: HTMLElement, onClick: () => void): void => {
        unbindTargetClick();

        targetRef.current = target;
        clickHandlerRef.current = onClick;
        target.addEventListener('click', onClick, { once: true });
      },
      [unbindTargetClick]
    );

    useEffect(() => unbindTargetClick, [unbindTargetClick]);

    return { bindTargetClick, unbindTargetClick };
  };
