import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerGemRemovalDataDefinition from '../../api/definitions/seer-gem-removal-data-definition';

export default interface SeerRemoveGemsFormProps {
  removalData: SeerGemRemovalDataDefinition | undefined;
  characterId: number;
  status: string | null;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
  onChangeAction: () => void;
}
