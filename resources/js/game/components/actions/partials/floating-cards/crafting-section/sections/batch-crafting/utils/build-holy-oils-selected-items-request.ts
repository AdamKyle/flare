import HolyOilsSelectedItemsRequestDefinition from '../api/definitions/holy-oils-selected-items-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { HolyOilsBatchMode } from '../enums/holy-oils-batch-mode';

interface BuildHolyOilsSelectedItemsRequestParams {
  targetSlotIds: number[];
  selectedOilId: number | null;
  disposition: BatchCraftingDisposition | null;
  listingPriceText: string;
}

export const buildHolyOilsSelectedItemsRequest = ({
  targetSlotIds,
  selectedOilId,
  disposition,
  listingPriceText,
}: BuildHolyOilsSelectedItemsRequestParams): HolyOilsSelectedItemsRequestDefinition | null => {
  if (targetSlotIds.length === 0 || selectedOilId === null) {
    return null;
  }

  if (!disposition) {
    return null;
  }

  const listingPrice = Number(listingPriceText);

  if (
    disposition === BatchCraftingDisposition.LIST &&
    (!Number.isFinite(listingPrice) || listingPrice < 1)
  ) {
    return null;
  }

  const oilSlotIds = selectedOilId !== null ? [selectedOilId] : [];

  return {
    batch_type: BatchCraftingType.HOLY_OILS,
    disposition,
    progress: {
      holy_oils_mode: HolyOilsBatchMode.SELECTED_ITEMS,
      target_slot_ids: targetSlotIds,
      oil_slot_ids: oilSlotIds,
      ...(disposition === BatchCraftingDisposition.LIST
        ? { listing_price: listingPrice }
        : {}),
    },
  };
};
