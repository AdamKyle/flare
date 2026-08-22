import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { HolyOilsBatchMode } from '../../enums/holy-oils-batch-mode';

export interface HolyOilsSetProgressDefinition {
  holy_oils_mode: HolyOilsBatchMode.INVENTORY_SET;
  inventory_set_id: number;
  oil_slot_ids: number[];
  listing_price?: number;
}

export default interface HolyOilsSetRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: HolyOilsSetProgressDefinition;
}
