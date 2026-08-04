import QueenOfHeartsApiResponseDefinition from '../../definitions/queen-of-hearts-api-response-definition';

export default interface UseRerollQueenAffixApiDefinition {
  submitting: boolean;
  error: string | null;
  reroll: () => Promise<QueenOfHeartsApiResponseDefinition | null>;
}
