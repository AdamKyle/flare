import React, { ReactNode } from 'react';

import { useReviveCharacter } from '../../api/hooks/use-revive-character';
import { useDismissExplorationWarning } from '../api/hooks/use-dismiss-exploration-warning';
import ExplorationDeathPanelProps from '../types/exploration-death-panel-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

const ExplorationDeathPanel = ({
  character_id: characterId,
  on_dismissed: onDismissed,
}: ExplorationDeathPanelProps): ReactNode => {
  const {
    loading: dismissing,
    error: dismissError,
    dismiss,
  } = useDismissExplorationWarning(characterId);
  const {
    loading: reviving,
    error: reviveError,
    revive,
  } = useReviveCharacter(characterId);

  const handleDismiss = async (): Promise<void> => {
    const result = await dismiss();

    if (result) {
      onDismissed();
    }
  };

  const handleRevive = async (): Promise<void> => {
    const revived = await revive();

    if (!revived) {
      return;
    }

    const result = await dismiss();

    if (result) {
      onDismissed();
    }
  };

  const isLoading = dismissing || reviving;

  return (
    <div className="space-y-3">
      <h3 className="text-lg font-semibold">Exploration Ended</h3>
      <Alert variant={AlertVariant.DANGER}>You died while exploring.</Alert>
      {dismissError && (
        <Alert variant={AlertVariant.DANGER}>{dismissError}</Alert>
      )}
      {reviveError && (
        <Alert variant={AlertVariant.DANGER}>{reviveError}</Alert>
      )}
      <div className="flex flex-col gap-2 sm:flex-row">
        <LoadingButton
          label="Dismiss"
          loading_label="Dismissing..."
          variant={ButtonVariant.PRIMARY}
          is_loading={dismissing}
          disabled={isLoading}
          on_click={() => void handleDismiss()}
          additional_css="w-full sm:flex-1"
        />
        <LoadingButton
          label="Revive Character"
          loading_label="Reviving..."
          variant={ButtonVariant.DANGER}
          is_loading={reviving}
          disabled={isLoading}
          on_click={() => void handleRevive()}
          additional_css="w-full sm:flex-1"
        />
      </div>
    </div>
  );
};

export default ExplorationDeathPanel;
