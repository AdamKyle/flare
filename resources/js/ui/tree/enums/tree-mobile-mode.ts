/**
 * Generic Tree responsive strategy below the `md` breakpoint.
 *
 * `TREE` always renders the actual visual top-to-bottom Tree on every
 * viewport. `ONLY_WHATS_AVAILABLE` renders a semantic card/list fallback of
 * only the currently available nodes below `md`, and the actual visual Tree
 * at `md` and larger.
 */
enum TreeMobileMode {
  TREE = 'tree',
  ONLY_WHATS_AVAILABLE = 'only_whats_available',
}

export default TreeMobileMode;
