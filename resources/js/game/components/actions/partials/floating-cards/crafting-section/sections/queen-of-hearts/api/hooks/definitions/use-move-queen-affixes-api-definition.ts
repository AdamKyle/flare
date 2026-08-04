import QueenOfHeartsApiResponseDefinition from '../../definitions/queen-of-hearts-api-response-definition';

export default interface UseMoveQueenAffixesApiDefinition {
  submitting: boolean;
  error: string | null;
  move: () => Promise<QueenOfHeartsApiResponseDefinition | null>;
}
