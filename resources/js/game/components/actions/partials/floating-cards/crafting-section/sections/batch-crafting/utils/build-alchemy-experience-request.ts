import AlchemyExperienceRequestDefinition from '../api/definitions/alchemy-experience-request-definition';
import { AlchemyBatchMode } from '../enums/alchemy-batch-mode';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingType } from '../enums/batch-crafting-type';

interface BuildAlchemyExperienceRequestParams {
  disposition: BatchCraftingDisposition | null;
  listingPriceText: string;
}

export const buildAlchemyExperienceRequest = ({
  disposition,
  listingPriceText,
}: BuildAlchemyExperienceRequestParams): AlchemyExperienceRequestDefinition | null => {
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

  return {
    batch_type: BatchCraftingType.ALCHEMY,
    disposition,
    progress: {
      alchemy_mode: AlchemyBatchMode.EXPERIENCE,
      ...(disposition === BatchCraftingDisposition.LIST
        ? { listing_price: listingPrice }
        : {}),
    },
  };
};
