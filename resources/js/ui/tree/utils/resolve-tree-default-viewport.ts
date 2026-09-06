import TreeDefaultViewportDefinition from '../definitions/tree-default-viewport-definition';

const TOP_MARGIN_PX = 56;

interface ResolveTreeDefaultViewportParams {
  container_width: number;
  focus_center_x: number;
  focus_top_y: number;
  default_zoom: number;
}

/**
 * Compute the canonical default Tree viewport: the configured focal
 * node/root anchor positioned near the upper-central portion of the
 * container at the configured default zoom. This is the single source of
 * viewport math shared by the Tree's initial positioning and its Reset
 * View control, so both always resolve to the same position.
 *
 * @param  params  Container width, focal anchor in flow-space coordinates, and configured default zoom.
 * @return  Resolved default Tree viewport.
 */
export const resolveTreeDefaultViewport = ({
  container_width: containerWidth,
  focus_center_x: focusCenterX,
  focus_top_y: focusTopY,
  default_zoom: defaultZoom,
}: ResolveTreeDefaultViewportParams): TreeDefaultViewportDefinition => ({
  x: containerWidth / 2 - focusCenterX * defaultZoom,
  y: TOP_MARGIN_PX - focusTopY * defaultZoom,
  zoom: defaultZoom,
});
