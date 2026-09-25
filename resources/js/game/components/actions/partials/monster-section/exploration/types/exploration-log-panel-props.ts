import ExplorationLogOutputDefinition from './exploration-log-output-definition';

export default interface ExplorationLogPanelProps {
  character_id: number;
  output: ExplorationLogOutputDefinition;
  state: 'active' | 'ended';
  on_stopped: () => void;
  on_dismissed: () => void;
}
