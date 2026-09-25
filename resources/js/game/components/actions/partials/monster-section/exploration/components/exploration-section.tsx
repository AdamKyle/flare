import React, { ReactNode } from 'react';

import ExplorationLogPanel from './exploration-log-panel';
import ExplorationRecoveryPanel from './exploration-recovery-panel';
import ExplorationWarningPanel from './exploration-warning-panel';
import { isExplorationWarningOutput } from '../types/exploration-output-response-definition';
import ExplorationSectionProps from '../types/exploration-section-props';

const ExplorationSection = ({
  character_id: characterId,
  status,
  on_refetch: onRefetch,
}: ExplorationSectionProps): ReactNode => {
  if (status?.type === 'active' && status.output) {
    return (
      <ExplorationLogPanel
        character_id={characterId}
        output={status.output}
        state="active"
        on_stopped={onRefetch}
        on_dismissed={onRefetch}
      />
    );
  }

  if (status?.type === 'ended' && status.output) {
    return (
      <ExplorationLogPanel
        character_id={characterId}
        output={status.output}
        state="ended"
        on_stopped={onRefetch}
        on_dismissed={onRefetch}
      />
    );
  }

  if (
    status?.type === 'warning' &&
    status.output &&
    isExplorationWarningOutput(status.output)
  ) {
    return (
      <ExplorationWarningPanel
        character_id={characterId}
        output={status.output}
        on_dismissed={onRefetch}
      />
    );
  }

  return (
    <ExplorationRecoveryPanel
      character_id={characterId}
      on_cancelled={onRefetch}
    />
  );
};

export default ExplorationSection;
