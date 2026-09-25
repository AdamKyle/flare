import ExplorationOutputResponseDefinition from './exploration-output-response-definition';

export default interface ExplorationSectionProps {
  character_id: number;
  status: ExplorationOutputResponseDefinition | null;
  on_refetch: () => void;
}
