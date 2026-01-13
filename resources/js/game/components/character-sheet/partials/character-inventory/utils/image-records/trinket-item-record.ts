import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const trinketItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.TRINKET]: `${EquipmentImagePaths.TRINKETS}/trinket.gif`,
};
