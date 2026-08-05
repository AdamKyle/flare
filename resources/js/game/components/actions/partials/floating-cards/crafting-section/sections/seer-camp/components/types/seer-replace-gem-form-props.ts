import { AttachedGemDefinition } from '../../api/definitions/gem-comparison-api-response-definition';

export default interface SeerReplaceGemFormProps {
  attachedGems: AttachedGemDefinition[];
  selectedGemId: number | null;
  onSelect: (gemId: number) => void;
}
