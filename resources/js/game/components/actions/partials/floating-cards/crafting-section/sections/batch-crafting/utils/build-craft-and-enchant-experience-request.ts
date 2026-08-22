import CraftAndEnchantExperienceRequestDefinition from '../api/definitions/craft-and-enchant-experience-request-definition';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';
import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';

export const buildCraftAndEnchantExperienceRequest = (
  disposition: BatchCraftingDisposition,
  listingPrice: number | null
): CraftAndEnchantExperienceRequestDefinition => ({
  batch_type: BatchCraftingType.CRAFT_AND_ENCHANT,
  disposition,
  progress: {
    craft_enchant_mode: CraftAndEnchantBatchMode.EXPERIENCE,
    ...(disposition === BatchCraftingDisposition.LIST && listingPrice
      ? { listing_price: listingPrice }
      : {}),
  },
});
