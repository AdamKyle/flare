import CraftAndEnchantAmountRequestDefinition from '../api/definitions/craft-and-enchant-amount-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';
import CraftAndEnchantOutputSelection from '../types/craft-and-enchant-output-selection';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MAX_CRAFT_AMOUNT = 2000;

interface BuildCraftAndEnchantAmountRequestParams {
  craftingType: DropdownItem | null;
  selectedItem: DropdownItem | null;
  prefix: DropdownItem | null;
  suffix: DropdownItem | null;
  amountText: string;
  outputSelection: CraftAndEnchantOutputSelection;
}

export const buildCraftAndEnchantAmountRequest = ({
  craftingType,
  selectedItem,
  prefix,
  suffix,
  amountText,
  outputSelection,
}: BuildCraftAndEnchantAmountRequestParams): CraftAndEnchantAmountRequestDefinition | null => {
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

  const prefixId = typeof prefix?.value === 'number' ? prefix.value : null;
  const suffixId = typeof suffix?.value === 'number' ? suffix.value : null;

  if (prefixId === null && suffixId === null) {
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

  const { disposition, output_destination, listing_price } = outputSelection;

  if (
    disposition === BatchCraftingDisposition.KEEP &&
    output_destination === null
  ) {
    return null;
  }

  if (disposition === BatchCraftingDisposition.LIST && !listing_price) {
    return null;
  }

  return {
    batch_type: BatchCraftingType.CRAFT_AND_ENCHANT,
    disposition,
    progress: {
      craft_enchant_mode: CraftAndEnchantBatchMode.AMOUNT,
      specific_crafting_type: craftingType.value,
      specific_item_id: selectedItem.value,
      prefix_id: prefixId,
      suffix_id: suffixId,
      craft_amount: amount,
      ...(disposition === BatchCraftingDisposition.KEEP && output_destination
        ? { output_destination }
        : {}),
      ...(disposition === BatchCraftingDisposition.LIST && listing_price
        ? { listing_price }
        : {}),
    },
  };
};
