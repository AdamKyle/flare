import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';

export default interface QueenRerollFormProps {
  data: QueenOfHeartsApiResponseDefinition;
  characterId: number;
  onSuccess: (message: string | undefined) => void;
  onDataReplaced: (data: QueenOfHeartsApiResponseDefinition) => void;
}
