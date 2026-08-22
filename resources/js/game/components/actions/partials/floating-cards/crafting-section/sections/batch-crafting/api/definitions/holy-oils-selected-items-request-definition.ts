import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { HolyOilsBatchMode } from '../../enums/holy-oils-batch-mode';

export interface HolyOilsSelectedItemsProgressDefinition {
  holy_oils_mode: HolyOilsBatchMode.SELECTED_ITEMS;
  target_slot_ids: number[];
  oil_slot_ids: number[];
  listing_price?: number;
}

export default interface HolyOilsSelectedItemsRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: HolyOilsSelectedItemsProgressDefinition;
}
