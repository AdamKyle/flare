import ExplorationWarningOutputDefinition from './exploration-warning-output-definition';

export default interface ExplorationWarningPanelProps {
  character_id: number;
  output: ExplorationWarningOutputDefinition;
  on_dismissed: () => void;
}
