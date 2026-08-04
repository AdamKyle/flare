import GemComparisonApiResponseDefinition from '../../definitions/gem-comparison-api-response-definition';
import GemComparisonRequestDefinition from '../../definitions/gem-comparison-request-definition';

export default interface UseGemComparisonApiDefinition {
  loading: boolean;
  error: string | null;
  compare: (
    request: GemComparisonRequestDefinition
  ) => Promise<GemComparisonApiResponseDefinition | null>;
}
