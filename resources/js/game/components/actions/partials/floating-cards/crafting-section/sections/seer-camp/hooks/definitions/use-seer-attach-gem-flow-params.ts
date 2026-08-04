import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerGemDefinition from '../../api/definitions/seer-gem-definition';
import SeerItemDefinition from '../../api/definitions/seer-item-definition';

export default interface UseSeerAttachGemFlowParams {
  characterId: number;
  items: SeerItemDefinition[];
  gems: SeerGemDefinition[];
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
