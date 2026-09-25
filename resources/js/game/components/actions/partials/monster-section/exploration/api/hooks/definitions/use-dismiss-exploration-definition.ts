import ExplorationOutputResponseDefinition from '../../../types/exploration-output-response-definition';

export default interface UseDismissExplorationDefinition {
  loading: boolean;
  error: string | null;
  dismiss: () => Promise<ExplorationOutputResponseDefinition | null>;
}
