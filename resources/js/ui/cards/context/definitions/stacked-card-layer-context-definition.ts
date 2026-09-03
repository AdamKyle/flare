/**
 * Nullable layer-host contract shared by the generic `SidePeek` shell and
 * `StackedCard`. `host_element` is the current layer's own full-panel
 * portal target for the *next* nested `FULL_BLEED` `StackedCard`.
 * `register_layer` must be called only by a covering layer that actually
 * portals into this `host_element` (a `FULL_BLEED` `StackedCard` that has a
 * valid parent host) to mark this layer as covered (and inert); its return
 * value must be called once on that covering layer's unmount to uncover
 * this layer again. A `PADDED` `StackedCard` never calls `register_layer`
 * on its parent, because it renders inside the parent's own content flow
 * rather than covering the parent's full panel.
 */
export default interface StackedCardLayerContextDefinition {
  host_element: HTMLDivElement | null;
  register_layer: () => () => void;
}
