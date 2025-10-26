import { useLayoutEffect, useMemo, useState } from 'react';

import UseTooltipPlacementDefinition from 'ui/tool-tips/hooks/definitions/use-tooltip-placement-definition';
import UseTooltipPlacementParams from 'ui/tool-tips/hooks/definitions/use-tooltip-placement-params';
import { getScrollParent } from 'ui/tool-tips/utils/get-scroll-parent';

const useTooltipPlacement = (
  params: UseTooltipPlacementParams
): UseTooltipPlacementDefinition => {
  const { containerRef, buttonRef, popoverRef, align, open, extraDeps } =
    params;

  const preferRight = align !== 'left';

  const [horizontal, setHorizontal] = useState<'left' | 'right'>(
    preferRight ? 'right' : 'left'
  );
  const [vertical, setVertical] = useState<'above' | 'below'>('below');

  const place = (): void => {
    const triggerEl = buttonRef.current;
    const tooltipEl = popoverRef.current;

    if (!triggerEl || !tooltipEl) {
      return;
    }

    const scrollParent = getScrollParent(containerRef.current);
    const parentRect = scrollParent
      ? scrollParent.getBoundingClientRect()
      : new DOMRect(0, 0, window.innerWidth, window.innerHeight);

    const triggerRect = triggerEl.getBoundingClientRect();

    const tooltipWidth = tooltipEl.offsetWidth || 256;
    const tooltipHeight = tooltipEl.offsetHeight || 120;

    const spaceRight = parentRect.right - triggerRect.right;
    const spaceLeft = triggerRect.left - parentRect.left;
    const spaceBelow = parentRect.bottom - triggerRect.bottom;
    const spaceAbove = triggerRect.top - parentRect.top;

    if (preferRight && spaceRight >= tooltipWidth + 4) {
      setHorizontal('right');
    } else if (!preferRight && spaceLeft >= tooltipWidth + 4) {
      setHorizontal('left');
    } else if (spaceRight >= spaceLeft) {
      setHorizontal('right');
    } else {
      setHorizontal('left');
    }

    if (spaceBelow >= tooltipHeight + 4) {
      setVertical('below');
    } else if (spaceAbove >= tooltipHeight + 4) {
      setVertical('above');
    } else {
      setVertical(spaceBelow >= spaceAbove ? 'below' : 'above');
    }
  };

  const deps = useMemo(() => {
    return [open, align, ...(extraDeps || [])];
  }, [open, align, extraDeps]);

  useLayoutEffect(() => {
    if (!open) {
      return;
    }

    place();

    const parent = getScrollParent(containerRef.current);

    const onScroll = () => {
      place();
    };

    const onResize = () => {
      place();
    };

    window.addEventListener('resize', onResize);
    parent?.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('scroll', onScroll, {
      passive: true,
      capture: true,
    });

    return () => {
      window.removeEventListener('resize', onResize);
      parent?.removeEventListener('scroll', onScroll);
      window.removeEventListener('scroll', onScroll, true);
    };
  }, deps);

  return {
    horizontal,
    vertical,
    place,
  };
};

export default useTooltipPlacement;
