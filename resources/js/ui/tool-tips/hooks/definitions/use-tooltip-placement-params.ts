import { RefObject } from 'react';

import { TooltipAlign, TooltipPlacement } from '../../types/tooltip-placement';

export default interface UseTooltipPlacementParams {
  buttonRef: RefObject<HTMLButtonElement | null>;
  popoverRef: RefObject<HTMLDivElement | null>;
  align: TooltipAlign;
  placement: TooltipPlacement;
  open: boolean;
}
