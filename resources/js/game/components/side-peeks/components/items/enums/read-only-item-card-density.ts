/**
 * Visual density for `ReadOnlyItemCard`. `DEFAULT` preserves the
 * established inventory-sized presentation (Character inventory). `COMPACT`
 * matches the density/layout quality of the canonical Quest/Location
 * relationship cards for factual Admin/detail relationship lists.
 */
enum ReadOnlyItemCardDensity {
  DEFAULT = 'default',
  COMPACT = 'compact',
}

export default ReadOnlyItemCardDensity;
