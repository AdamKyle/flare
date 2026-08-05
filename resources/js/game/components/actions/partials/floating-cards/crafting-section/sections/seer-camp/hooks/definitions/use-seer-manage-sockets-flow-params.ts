import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';

export default interface UseSeerManageSocketsFlowParams {
  characterId: number;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
