import { TooltipAlign, TooltipPlacement } from './tooltip-placement';

export default interface ResolveTooltipPositionParams {
  trigger_rect: DOMRect;
  popover_width: number;
  popover_height: number;
  viewport_width: number;
  viewport_height: number;
  placement: TooltipPlacement;
  align: TooltipAlign;
}
