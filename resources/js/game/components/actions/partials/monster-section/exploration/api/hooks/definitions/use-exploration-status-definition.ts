import ExplorationOutputResponseDefinition from '../../../types/exploration-output-response-definition';

export default interface UseExplorationStatusDefinition {
  data: ExplorationOutputResponseDefinition | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}
