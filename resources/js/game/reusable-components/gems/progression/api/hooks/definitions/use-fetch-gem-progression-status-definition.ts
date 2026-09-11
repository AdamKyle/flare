import GemProgressionStatusDefinition from '../../definitions/gem-progression-status-definition';

export default interface UseFetchGemProgressionStatusDefinition {
  data: GemProgressionStatusDefinition | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}
