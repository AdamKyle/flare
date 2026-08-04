import { EnchantingItemSource } from '../../../enums/enchanting-item-source';

export default interface UseEnchantItemApiParams {
  characterId: number;
  slotId: number | null;
  affixIds: number[];
  source: EnchantingItemSource | null;
}
