import { ReactNode } from 'react';

import SeerCampApiResponseDefinition from '../../api/definitions/seer-camp-api-response-definition';
import SeerCampCostsDefinition from '../../api/definitions/seer-camp-costs-definition';

export default interface SeerManageSocketsFormProps {
  costs: SeerCampCostsDefinition;
  characterId: number;
  rootStatus?: ReactNode;
  helpLink?: ReactNode;
  onSuccess: (data: SeerCampApiResponseDefinition) => void;
  onChangeAction: () => void;
}
