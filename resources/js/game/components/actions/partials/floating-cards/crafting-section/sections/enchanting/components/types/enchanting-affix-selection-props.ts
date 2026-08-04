import EnchantingAffixDefinition from '../../api/definitions/enchanting-affix-definition';
export default interface EnchantingAffixSelectionProps {
  affixes: EnchantingAffixDefinition[];
  selectedPrefixId: number | null;
  selectedSuffixId: number | null;
  onPrefix: (id: number | null) => void;
  onSuffix: (id: number | null) => void;
}
