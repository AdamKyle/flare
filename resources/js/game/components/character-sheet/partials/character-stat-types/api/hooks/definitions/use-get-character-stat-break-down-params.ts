import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';

import { StatTypes } from '../../../../../enums/stat-types';

export default interface UseGetCharacterStatBreakDownParams extends ApiParametersDefinitions {
  statType: StatTypes;
}
