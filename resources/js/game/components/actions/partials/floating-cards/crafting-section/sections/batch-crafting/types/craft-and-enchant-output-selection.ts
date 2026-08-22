import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';

export default interface CraftAndEnchantOutputSelection {
  disposition: BatchCraftingDisposition;
  output_destination: BatchCraftingOutputDestination | null;
  output_set_id: number | null;
  output_set_name: string | null;
  listing_price: number | null;
}
