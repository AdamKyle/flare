import HolyOilsSetRequestDefinition from '../api/definitions/holy-oils-set-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { HolyOilsBatchMode } from '../enums/holy-oils-batch-mode';

interface BuildHolyOilsSetRequestParams {
  inventorySetId: number | null;
  selectedOilId: number | null;
  disposition: BatchCraftingDisposition | null;
  listingPriceText: string;
}

export const buildHolyOilsSetRequest = ({
  inventorySetId,
  selectedOilId,
  disposition,
  listingPriceText,
}: BuildHolyOilsSetRequestParams): HolyOilsSetRequestDefinition | null => {
  if (!inventorySetId || selectedOilId === null) {
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
      holy_oils_mode: HolyOilsBatchMode.INVENTORY_SET,
      inventory_set_id: inventorySetId,
      oil_slot_ids: oilSlotIds,
      ...(disposition === BatchCraftingDisposition.LIST
        ? { listing_price: listingPrice }
        : {}),
    },
  };
};
