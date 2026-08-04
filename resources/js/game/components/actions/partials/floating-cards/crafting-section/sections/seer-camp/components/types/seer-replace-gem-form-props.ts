import { AttachedGemDefinition } from '../../api/definitions/gem-comparison-api-response-definition';

export default interface SeerReplaceGemFormProps {
  attachedGems: AttachedGemDefinition[];
  selectedGemId: number | null;
  replaceCost: number;
  submitting: boolean;
  onSelect: (gemId: number) => void;
  onSubmit: () => void;
}
