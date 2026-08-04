import { EnchantingItemSource } from '../../enums/enchanting-item-source';
export default interface EnchantingSourceSelectionProps {
  onSelect: (source: EnchantingItemSource) => void;
}
