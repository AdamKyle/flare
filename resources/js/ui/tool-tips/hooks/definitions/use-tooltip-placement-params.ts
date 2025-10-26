import { RefObject } from 'react';

export default interface UseTooltipPlacementParams {
  containerRef: RefObject<HTMLSpanElement | null>;
  buttonRef: RefObject<HTMLButtonElement | null>;
  popoverRef: RefObject<HTMLDivElement | null>;
  align: 'left' | 'right' | 'auto' | undefined;
  open: boolean;
  extraDeps?: unknown[];
}
