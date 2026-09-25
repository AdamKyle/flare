import React, { ReactNode } from 'react';

import { useStopExploration } from '../api/hooks/use-stop-exploration';
import ExplorationRecoveryPanelProps from '../types/exploration-recovery-panel-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const ExplorationRecoveryPanel = ({
  character_id: characterId,
  on_cancelled: onCancelled,
}: ExplorationRecoveryPanelProps): ReactNode => {
  const { loading, error, stop } = useStopExploration(characterId);

  const handleCancel = async (): Promise<void> => {
    const stopped = await stop();

    if (stopped) {
      onCancelled();
    }
  };

  return (
    <div className="space-y-3">
      <h3 className="text-lg font-semibold">Exploration</h3>

      <Alert variant={AlertVariant.DANGER}>
        Exploration is active, but its current status could not be loaded.
        Cancel Exploration to return to manual fighting.
      </Alert>

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <Button
        label="Cancel Exploration"
        variant={ButtonVariant.DANGER}
        additional_css="w-full"
        disabled={loading}
        aria_busy={loading}
        on_click={handleCancel}
      />
    </div>
  );
};

export default ExplorationRecoveryPanel;
