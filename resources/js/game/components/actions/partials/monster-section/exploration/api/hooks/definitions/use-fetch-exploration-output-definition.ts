import ExplorationOutputResponseDefinition from '../../../types/exploration-output-response-definition';

export default interface UseFetchExplorationOutputDefinition {
  data: ExplorationOutputResponseDefinition | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}
