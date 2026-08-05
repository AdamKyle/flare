import { ReactNode } from 'react';

import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';

export default interface QueenMoveAffixesFormProps {
  data: QueenOfHeartsApiResponseDefinition;
  characterId: number;
  rootStatus?: ReactNode;
  helpLink?: ReactNode;
  onSuccess: (message: string | undefined) => void;
  onDataReplaced: (data: QueenOfHeartsApiResponseDefinition) => void;
  onChangeAction: () => void;
}
