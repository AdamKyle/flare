import React, { ReactNode } from 'react';

import { useDismissExplorationWarning } from '../api/hooks/use-dismiss-exploration-warning';
import ExplorationWarningPanelProps from '../types/exploration-warning-panel-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const ExplorationWarningPanel = ({
  character_id: characterId,
  output,
  on_dismissed: onDismissed,
}: ExplorationWarningPanelProps): ReactNode => {
  const { loading, error, dismiss } = useDismissExplorationWarning(characterId);

  const handleDismiss = async (): Promise<void> => {
    const result = await dismiss();

    if (result) {
      onDismissed();
    }
  };

  return (
    <div className="space-y-3">
      <h3 className="text-lg font-semibold">Exploration Warning</h3>

      <Alert variant={AlertVariant.WARNING}>{output.message}</Alert>

      {output.fights > 0 && (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          The last run before this warning recorded {output.fights} fight
          {output.fights === 1 ? '' : 's'} and {output.kills} kill
          {output.kills === 1 ? '' : 's'} before it stopped.
        </p>
      )}

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <Button
        label="Dismiss"
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        disabled={loading}
        aria_busy={loading}
        on_click={handleDismiss}
      />
    </div>
  );
};

export default ExplorationWarningPanel;
