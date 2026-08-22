import AlchemyAmountRequestDefinition from '../api/definitions/alchemy-amount-request-definition';
import { AlchemyBatchMode } from '../enums/alchemy-batch-mode';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MAX_AMOUNT = 2000;

interface BuildAlchemyAmountRequestParams {
  selectedItem: DropdownItem | null;
  amountText: string;
  disposition: BatchCraftingDisposition | null;
  listingPriceText: string;
}

export const buildAlchemyAmountRequest = ({
  selectedItem,
  amountText,
  disposition,
  listingPriceText,
}: BuildAlchemyAmountRequestParams): AlchemyAmountRequestDefinition | null => {
  if (
    !selectedItem ||
    typeof selectedItem.value !== 'number' ||
    !Number.isInteger(selectedItem.value) ||
    selectedItem.value <= 0
  ) {
    return null;
  }

  if (!disposition) {
    return null;
  }

  const amount = Number(amountText);

  if (
    !Number.isFinite(amount) ||
    !Number.isInteger(amount) ||
    amount < 1 ||
    amount > MAX_AMOUNT
  ) {
    return null;
  }

  const listingPrice = Number(listingPriceText);

  if (
    disposition === BatchCraftingDisposition.LIST &&
    (!Number.isFinite(listingPrice) || listingPrice < 1)
  ) {
    return null;
  }

  return {
    batch_type: BatchCraftingType.ALCHEMY,
    disposition,
    progress: {
      alchemy_mode: AlchemyBatchMode.AMOUNT,
      alchemy_item_id: selectedItem.value,
      alchemy_amount: amount,
      ...(disposition === BatchCraftingDisposition.LIST
        ? { listing_price: listingPrice }
        : {}),
    },
  };
};
