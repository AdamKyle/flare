import ResolveTooltipPositionParams from '../types/resolve-tooltip-position-params';
import { TooltipSide } from '../types/tooltip-placement';
import TooltipPosition from '../types/tooltip-position';

const VIEWPORT_MARGIN = 8;
const TRIGGER_GAP = 6;

const clamp = (value: number, minimum: number, maximum: number): number =>
  Math.min(Math.max(value, minimum), Math.max(minimum, maximum));

const resolveSideOrder = (
  params: ResolveTooltipPositionParams
): TooltipSide[] => {
  if (params.placement === 'above') {
    return ['above', 'below', 'right', 'left'];
  }

  if (params.align === 'left') {
    return ['left', 'right', 'below', 'above'];
  }

  return ['right', 'left', 'below', 'above'];
};

const positionForSide = (
  side: TooltipSide,
  params: ResolveTooltipPositionParams
): TooltipPosition => {
  const { trigger_rect: rect, popover_width, popover_height } = params;
  const centeredLeft = rect.left + rect.width / 2 - popover_width / 2;

  if (side === 'above') {
    return { top: rect.top - popover_height - TRIGGER_GAP, left: centeredLeft };
  }

  if (side === 'below') {
    return { top: rect.bottom + TRIGGER_GAP, left: centeredLeft };
  }

  if (side === 'left') {
    return { top: rect.top, left: rect.left - popover_width - TRIGGER_GAP };
  }

  return { top: rect.top, left: rect.right + TRIGGER_GAP };
};

const availableSpace = (
  side: TooltipSide,
  params: ResolveTooltipPositionParams
): number => {
  const { trigger_rect: rect, viewport_width, viewport_height } = params;

  if (side === 'above') {
    return rect.top - VIEWPORT_MARGIN;
  }

  if (side === 'below') {
    return viewport_height - rect.bottom - VIEWPORT_MARGIN;
  }

  if (side === 'left') {
    return rect.left - VIEWPORT_MARGIN;
  }

  return viewport_width - rect.right - VIEWPORT_MARGIN;
};

const requiredSpace = (
  side: TooltipSide,
  params: ResolveTooltipPositionParams
): number => {
  if (side === 'above' || side === 'below') {
    return params.popover_height + TRIGGER_GAP;
  }

  return params.popover_width + TRIGGER_GAP;
};

const resolveSide = (params: ResolveTooltipPositionParams): TooltipSide => {
  const sides = resolveSideOrder(params);
  const fittingSide = sides.find(
    (side) => availableSpace(side, params) >= requiredSpace(side, params)
  );

  if (fittingSide) {
    return fittingSide;
  }

  return sides.reduce((roomiestSide, side) =>
    availableSpace(side, params) > availableSpace(roomiestSide, params)
      ? side
      : roomiestSide
  );
};

/**
 * Returns viewport (fixed) coordinates for a tooltip beside its trigger,
 * preferring the requested side, falling back to whichever side fits, and
 * clamping the result inside the viewport margins.
 */
export const resolveTooltipPosition = (
  params: ResolveTooltipPositionParams
): TooltipPosition => {
  const position = positionForSide(resolveSide(params), params);

  return {
    top: clamp(
      position.top,
      VIEWPORT_MARGIN,
      params.viewport_height - params.popover_height - VIEWPORT_MARGIN
    ),
    left: clamp(
      position.left,
      VIEWPORT_MARGIN,
      params.viewport_width - params.popover_width - VIEWPORT_MARGIN
    ),
  };
};
