import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerCampCostsDefinition from '../../api/definitions/seer-camp-costs-definition';
import SeerGemDefinition from '../../api/definitions/seer-gem-definition';
import SeerItemDefinition from '../../api/definitions/seer-item-definition';
export default interface SeerAttachGemFormProps {
  items: SeerItemDefinition[];
  gems: SeerGemDefinition[];
  costs: SeerCampCostsDefinition;
  characterId: number;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
