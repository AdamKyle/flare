import QueenOfHeartsApiResponseDefinition from '../../api/definitions/queen-of-hearts-api-response-definition';

export default interface QueenRerollFormProps {
  data: QueenOfHeartsApiResponseDefinition;
  characterId: number;
  onDataReplaced: (data: QueenOfHeartsApiResponseDefinition) => void;
  onChangeAction: () => void;
}
