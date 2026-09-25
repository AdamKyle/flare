import React, { ReactNode } from 'react';

import ExplorationCharacterProgress from './exploration-character-progress';
import ExplorationCombatSummarySection from './exploration-combat-summary-section';
import ExplorationCurrenciesSection from './exploration-currencies-section';
import ExplorationDetailSection from './exploration-detail-section';
import ExplorationElapsedTime from './exploration-elapsed-time';
import ExplorationProgressSection from './exploration-progress-section';
import { useDismissExplorationEnded } from '../api/hooks/use-dismiss-exploration-ended';
import { useStopExploration } from '../api/hooks/use-stop-exploration';
import ExplorationLogPanelProps from '../types/exploration-log-panel-props';
import { formatExplorationDuration } from '../utils/format-exploration-duration';
import { resolveExplorationAttackTypeLabel } from '../utils/resolve-exploration-attack-type-label';
import { resolveExplorationPhaseStatus } from '../utils/resolve-exploration-phase-status';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const ExplorationLogPanel = ({
  character_id: characterId,
  output,
  state,
  on_stopped: onStopped,
  on_dismissed: onDismissed,
}: ExplorationLogPanelProps): ReactNode => {
  const {
    loading: stopping,
    error: stopError,
    stop,
  } = useStopExploration(characterId);
  const {
    loading: dismissing,
    error: dismissError,
    dismiss,
  } = useDismissExplorationEnded(characterId);

  const isActive = state === 'active';
  const heading = isActive ? 'Exploration' : 'Exploration Ended';
  const phaseStatus = resolveExplorationPhaseStatus(
    output.phase,
    output.current_round_creatures
  );

  const handleCancel = async (): Promise<void> => {
    const stopped = await stop();

    if (stopped) {
      onStopped();
    }
  };

  const handleDismiss = async (): Promise<void> => {
    const result = await dismiss();

    if (result) {
      onDismissed();
    }
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between gap-3">
        <h3 className="text-lg font-semibold">{heading}</h3>
        <p
          role="status"
          aria-live="polite"
          className="text-regent-st-blue-700 dark:text-regent-st-blue-300 shrink-0 text-sm font-semibold"
        >
          {isActive ? 'Running' : 'Ended'}
        </p>
      </div>

      {isActive && (
        <p role="status" aria-live="polite" className="text-sm font-medium">
          {phaseStatus}
        </p>
      )}

      {isActive && (
        <Alert variant={AlertVariant.INFO}>
          You are currently busy with Exploration automation.
        </Alert>
      )}

      <ExplorationDetailSection title="Status">
        <Dl>
          <Dt>Attack Type</Dt>
          <Dd>{resolveExplorationAttackTypeLabel(output.attack_type)}</Dd>
          <Dt>{isActive ? 'Elapsed' : 'Duration'}</Dt>
          <Dd>
            {isActive ? (
              <ExplorationElapsedTime
                started_at={output.started_at}
                ended_at={output.ended_at}
              />
            ) : (
              formatExplorationDuration(output.duration)
            )}
          </Dd>
          {isActive && output.monster.name && (
            <>
              <Dt>Current Monster</Dt>
              <Dd>{output.monster.name}</Dd>
            </>
          )}
          {isActive && output.current_round_creatures > 0 && (
            <>
              <Dt>Creatures This Round</Dt>
              <Dd>{formatNumberWithCommas(output.current_round_creatures)}</Dd>
            </>
          )}
          {!isActive && output.stopped_reason && (
            <>
              <Dt>Reason</Dt>
              <Dd>{output.stopped_reason}</Dd>
            </>
          )}
        </Dl>
      </ExplorationDetailSection>

      <ExplorationDetailSection title="Character Progress">
        <ExplorationCharacterProgress />
      </ExplorationDetailSection>

      <ExplorationProgressSection
        chart_points={output.chart_points}
        totals={output.totals}
      />

      <ExplorationCurrenciesSection
        chart_points={output.chart_points}
        currencies={output.currencies}
      />

      <ExplorationCombatSummarySection
        chart_points={output.chart_points}
        damage={output.damage}
        healing={output.healing}
        blocked={output.blocked}
      />

      {stopError && <Alert variant={AlertVariant.DANGER}>{stopError}</Alert>}
      {dismissError && (
        <Alert variant={AlertVariant.DANGER}>{dismissError}</Alert>
      )}

      {isActive ? (
        <Button
          label="Cancel Exploration"
          variant={ButtonVariant.DANGER}
          additional_css="w-full"
          disabled={stopping}
          aria_busy={stopping}
          on_click={handleCancel}
        />
      ) : (
        <Button
          label="Dismiss"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full"
          disabled={dismissing}
          aria_busy={dismissing}
          on_click={handleDismiss}
        />
      )}
    </div>
  );
};

export default ExplorationLogPanel;
