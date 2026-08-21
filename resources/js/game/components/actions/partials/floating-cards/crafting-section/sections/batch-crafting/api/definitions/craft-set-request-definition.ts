import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftingBatchMode } from '../../enums/crafting-batch-mode';

export interface CraftSetPositionsDefinition {
  left_hand?: number;
  right_hand?: number;
  body: number;
  leggings: number;
  sleeves: number;
  gloves: number;
  feet: number;
  helmet: number;
  ring_0: number;
  ring_1: number;
  'spell-damage': number;
  'spell-healing': number;
}

export interface CraftSetProgressDefinition {
  craft_mode: CraftingBatchMode;
  set_positions: CraftSetPositionsDefinition;
  output_destination?: BatchCraftingOutputDestination;
  output_set_id?: number;
}

export default interface CraftSetRequestDefinition {
  batch_type: BatchCraftingType;
  disposition: BatchCraftingDisposition;
  progress: CraftSetProgressDefinition;
}
