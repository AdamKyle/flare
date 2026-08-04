import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';
import { QueenAction } from '../../enums/queen-action';

export default interface UseQueenOfHeartsFlowDefinition {
  characterId: number;
  data: QueenOfHeartsApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  status: string | null;
  action: QueenAction | null;
  hasData: boolean;
  replaceData: (data: QueenOfHeartsApiResponseDefinition) => void;
  selectAction: (action: QueenAction) => void;
  resetAction: () => void;
  handleRerollSuccess: (message?: string) => void;
  handleMoveSuccess: (message?: string) => void;
}
