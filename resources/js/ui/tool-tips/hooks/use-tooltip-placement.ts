import { useCallback, useLayoutEffect, useState } from 'react';

import UseTooltipPlacementDefinition from 'ui/tool-tips/hooks/definitions/use-tooltip-placement-definition';
import UseTooltipPlacementParams from 'ui/tool-tips/hooks/definitions/use-tooltip-placement-params';
import TooltipPosition from 'ui/tool-tips/types/tooltip-position';
import { resolveTooltipPosition } from 'ui/tool-tips/utils/resolve-tooltip-position';

const useTooltipPlacement = (
  params: UseTooltipPlacementParams
): UseTooltipPlacementDefinition => {
  const { buttonRef, popoverRef, align, placement, open } = params;

  const [position, setPosition] = useState<TooltipPosition | null>(null);

  const place = useCallback((): void => {
    const triggerElement = buttonRef.current;
    const popoverElement = popoverRef.current;

    if (!triggerElement || !popoverElement) {
      return;
    }

    setPosition(
      resolveTooltipPosition({
        trigger_rect: triggerElement.getBoundingClientRect(),
        popover_width: popoverElement.offsetWidth,
        popover_height: popoverElement.offsetHeight,
        viewport_width: window.innerWidth,
        viewport_height: window.innerHeight,
        placement,
        align,
      })
    );
  }, [align, placement, buttonRef, popoverRef]);

  useLayoutEffect(() => {
    if (!open) {
      setPosition(null);

      return;
    }

    place();

    const resizeObserver = new ResizeObserver(place);

    if (popoverRef.current) {
      resizeObserver.observe(popoverRef.current);
    }

    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, { passive: true, capture: true });

    return () => {
      resizeObserver.disconnect();
      window.removeEventListener('resize', place);
      window.removeEventListener('scroll', place, true);
    };
  }, [open, place, popoverRef]);

  return { position };
};

export default useTooltipPlacement;
