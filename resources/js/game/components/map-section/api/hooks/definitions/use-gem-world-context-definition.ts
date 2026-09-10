import GemWorldStatusDefinition from '../../definitions/gem-world-status-definition';

export default interface UseGemWorldContextDefinition {
  data: GemWorldStatusDefinition | null;
  loading: boolean;
  error: string | null;
}
