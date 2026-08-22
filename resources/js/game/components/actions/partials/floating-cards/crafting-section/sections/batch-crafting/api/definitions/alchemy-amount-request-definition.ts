import { AlchemyBatchMode } from '../../enums/alchemy-batch-mode';
import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../../enums/batch-crafting-type';

export interface AlchemyAmountProgressDefinition {
  alchemy_mode: AlchemyBatchMode.AMOUNT;
  alchemy_item_id: number;
  alchemy_amount: number;
  listing_price?: number;
}

export default interface AlchemyAmountRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: AlchemyAmountProgressDefinition;
}
