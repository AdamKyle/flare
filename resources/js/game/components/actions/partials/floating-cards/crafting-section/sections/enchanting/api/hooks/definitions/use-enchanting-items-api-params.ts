import { EnchantingItemSource } from '../../../enums/enchanting-item-source';

export default interface UseEnchantingItemsApiParams {
  character_id: number;
  source: EnchantingItemSource | null;
}
