import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';

export default interface UseSeerAttachGemFlowParams {
  characterId: number;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
