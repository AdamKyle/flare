import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingEndReason } from '../../enums/batch-crafting-end-reason';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { BatchCraftingType } from '../../enums/batch-crafting-type';
import { CraftingBatchMode } from '../../enums/crafting-batch-mode';

export interface BatchCraftingBatchStatusDefinition {
  id: number;
  batch_type: BatchCraftingType;
  craft_mode: CraftingBatchMode | null;
  disposition: BatchCraftingDisposition;
  status: BatchCraftingStatus;
  ended_reason: BatchCraftingEndReason | null;
  current_item_name: string | null;
  output_destination: BatchCraftingOutputDestination | null;
  requested_amount: number | null;
  completed_amount: number;
  remaining_amount: number | null;
  gold_left: number;
}

export default interface BatchCraftingStatusDefinition {
  active: boolean;
  is_running: boolean;
  is_visible: boolean;
  can_cancel: boolean;
  can_dismiss: boolean;
  show_info: boolean;
  batch: BatchCraftingBatchStatusDefinition | null;
}
