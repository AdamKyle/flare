import { CraftSetPositionsDefinition } from './craft-set-request-definition';
import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../../enums/craft-and-enchant-batch-mode';

export interface CraftAndEnchantSetEnchantmentEntryDefinition {
  prefix_id: number | null;
  suffix_id: number | null;
}

export type CraftAndEnchantSetEnchantmentsDefinition = Partial<
  Record<
    keyof CraftSetPositionsDefinition,
    CraftAndEnchantSetEnchantmentEntryDefinition
  >
>;

export interface CraftAndEnchantSetProgressDefinition {
  craft_enchant_mode: CraftAndEnchantBatchMode;
  set_positions: CraftSetPositionsDefinition;
  enchantments: CraftAndEnchantSetEnchantmentsDefinition;
  output_destination?: BatchCraftingOutputDestination;
  output_set_id?: number;
  listing_price?: number;
}

export default interface CraftAndEnchantSetRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: CraftAndEnchantSetProgressDefinition;
}
