import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerItemDefinition from '../../api/definitions/seer-item-definition';

export default interface UseSeerManageSocketsFlowParams {
  characterId: number;
  items: SeerItemDefinition[];
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
