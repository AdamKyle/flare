import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import { SeerAction } from '../../enums/seer-action';

export default interface UseSeerCampFlowDefinition {
  characterId: number;
  data: SeerCampApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  removalLoading: boolean;
  removalError: string | null;
  action: SeerAction | null;
  status: string | null;
  selectAction: (action: SeerAction) => void;
  changeAction: () => void;
  handleActionSuccess: (response: SeerCampApiResponseDefinition) => void;
}
