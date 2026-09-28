import CharacterGemDefinition from '../../../../../../../../../api-definitions/gems/character-gem-definition';

export default interface SeerReplaceGemFormProps {
  attachedGems: CharacterGemDefinition[];
  selectedGemId: number | null;
  onSelect: (gemId: number) => void;
}
