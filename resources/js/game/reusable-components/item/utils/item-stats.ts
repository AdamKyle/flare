import ItemStatDefinition from './definitions/item-stat-definition';
import { STAT_DEFINITIONS } from './definitions/stat-definitions';
import { Detail } from '../../../api-definitions/items/item-comparison-details';
import ItemDetails from '../../../api-definitions/items/item-details';

export function getItemStats(
  item: ItemDetails | Detail,
  isAdjustment = false
): ItemStatDefinition[] {
  return STAT_DEFINITIONS.map(({ label, baseKey, adjKey, isPercent }) => ({
    label,

    value: isAdjustment
      ? (item as Detail)[adjKey]
      : (item as ItemDetails)[baseKey],
    isPercent,
  }));
}
