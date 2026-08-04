import QueenOfHeartsApiResponseDefinition from '../../definitions/queen-of-hearts-api-response-definition';

export default interface UseQueenOfHeartsApiDefinition {
  data: QueenOfHeartsApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  replaceData: (data: QueenOfHeartsApiResponseDefinition) => void;
}
