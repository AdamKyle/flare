import CraftAmountRequestDefinition from '../api/definitions/craft-amount-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MAX_CRAFT_AMOUNT = 2000;

interface BuildCraftAmountRequestParams {
  craftingType: DropdownItem | null;
  selectedItem: DropdownItem | null;
  amountText: string;
  disposition: BatchCraftingDisposition;
  outputDestination: BatchCraftingOutputDestination;
}

export const buildCraftAmountRequest = ({
  craftingType,
  selectedItem,
  amountText,
  disposition,
  outputDestination,
}: BuildCraftAmountRequestParams): CraftAmountRequestDefinition | null => {
  if (!craftingType || typeof craftingType.value !== 'string') {
    return null;
  }

  if (
    !selectedItem ||
    typeof selectedItem.value !== 'number' ||
    !Number.isInteger(selectedItem.value) ||
    selectedItem.value <= 0
  ) {
    return null;
  }

  const amount = Number(amountText);

  if (
    !Number.isFinite(amount) ||
    !Number.isInteger(amount) ||
    amount < 1 ||
    amount > MAX_CRAFT_AMOUNT
  ) {
    return null;
  }

  return {
    batch_type: BatchCraftingType.CRAFT,
    disposition,
    progress: {
      craft_mode: CraftingBatchMode.AMOUNT,
      specific_crafting_type: craftingType.value,
      specific_item_id: selectedItem.value,
      craft_amount: amount,
      ...(disposition === BatchCraftingDisposition.KEEP
        ? { output_destination: outputDestination }
        : {}),
    },
  };
};
