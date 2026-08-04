import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerGemRemovalDataDefinition from '../../api/definitions/seer-gem-removal-data-definition';

export default interface UseSeerRemoveGemsFlowParams {
  removalData: SeerGemRemovalDataDefinition | undefined;
  characterId: number;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
