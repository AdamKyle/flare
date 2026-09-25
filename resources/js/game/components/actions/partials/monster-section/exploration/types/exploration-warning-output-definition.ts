import ExplorationLogOutputDefinition from './exploration-log-output-definition';

export default interface ExplorationWarningOutputDefinition extends ExplorationLogOutputDefinition {
  exploration_log_id: number | null;
  type: string;
}
