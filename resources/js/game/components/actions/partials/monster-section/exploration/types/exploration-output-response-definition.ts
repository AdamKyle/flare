import ExplorationLogOutputDefinition from './exploration-log-output-definition';
import ExplorationWarningOutputDefinition from './exploration-warning-output-definition';

export type ExplorationOutputPanelType = 'active' | 'warning' | 'ended';

export default interface ExplorationOutputResponseDefinition {
  type: ExplorationOutputPanelType | null;
  output:
    ExplorationLogOutputDefinition | ExplorationWarningOutputDefinition | null;
}

export const isExplorationWarningOutput = (
  output: ExplorationLogOutputDefinition | ExplorationWarningOutputDefinition
): output is ExplorationWarningOutputDefinition =>
  'exploration_log_id' in output;
