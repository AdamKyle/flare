import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerCampCostsDefinition from '../../api/definitions/seer-camp-costs-definition';
import SeerItemDefinition from '../../api/definitions/seer-item-definition';
export default interface SeerManageSocketsFormProps {
  items: SeerItemDefinition[];
  costs: SeerCampCostsDefinition;
  characterId: number;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
}
