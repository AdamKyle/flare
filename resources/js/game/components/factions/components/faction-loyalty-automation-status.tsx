import React, { ReactNode, useId } from 'react';

import FactionLoyaltyTaskList from './faction-loyalty-task-list';
import FactionLoyaltyWarnings from './faction-loyalty-warnings';
import FactionLoyaltyAutomationStatusProps from './types/faction-loyalty-automation-status-props';
import { formatExplorationDuration } from '../../actions/partials/monster-section/exploration/utils/format-exploration-duration';
import { useFactionLoyaltyAutomationActions } from '../api/hooks/use-faction-loyalty-automation-actions';
import { useFactionLoyaltyContext } from '../hooks/use-faction-loyalty-context';
import { resolveAssistedFactionLoyaltyNpc } from '../utils/resolve-assisted-faction-loyalty-npc';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const FactionLoyaltyAutomationStatus = ({
  character_id: characterId,
  active_automation: activeAutomation,
}: FactionLoyaltyAutomationStatusProps): ReactNode => {
  const { info, loading, error, warning_notices, apply_warning_state } =
    useFactionLoyaltyContext();
  const {
    submitting,
    error: actionError,
    stop,
    dismiss_warning: dismissWarning,
  } = useFactionLoyaltyAutomationActions(characterId);

  const headingId = useId();
  const fameLabelId = useId();

  const assistedNpc = resolveAssistedFactionLoyaltyNpc(info);

  const handleStop = () => {
    void stop();
  };

  const handleDismissWarning = async (warningId: number) => {
    const warningState = await dismissWarning(warningId);

    if (warningState === null) {
      return;
    }

    apply_warning_state(warningState);
  };

  const renderAssistedNpc = (): ReactNode => {
    if (loading && info === null) {
      return <InfiniteLoader />;
    }

    if (error !== null) {
      return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
    }

    if (assistedNpc === null) {
      return (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          You are not currently assisting an NPC.
        </p>
      );
    }

    return (
      <div className="space-y-3">
        <Dl>
          <Dt>Assisting</Dt>
          <Dd>{assistedNpc.npc.real_name}</Dd>
          <Dt>Level</Dt>
          <Dd>
            {assistedNpc.current_level} / {assistedNpc.max_level}
          </Dd>
        </Dl>
        <ProgressBar
          label="Fame towards next level"
          aria_labelledby={fameLabelId}
          value={assistedNpc.current_fame}
          max={assistedNpc.next_level_fame}
          value_label={`${assistedNpc.current_fame} / ${assistedNpc.next_level_fame}`}
          variant={ProgressBarVariant.XP}
        />
        <FactionLoyaltyTaskList
          tasks={assistedNpc.faction_loyalty_npc_tasks?.fame_tasks ?? []}
        />
      </div>
    );
  };

  const renderActionError = (): ReactNode => {
    if (actionError === null) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{actionError}</Alert>;
  };

  return (
    <section aria-labelledby={headingId} className="space-y-3">
      <div className="flex items-center justify-between gap-3">
        <h3
          id={headingId}
          className="text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          Faction Loyalty Automation
        </h3>
        <p
          role="status"
          aria-live="polite"
          className="text-regent-st-blue-700 dark:text-regent-st-blue-300 shrink-0 text-sm font-semibold"
        >
          Running
        </p>
      </div>

      <Dl>
        <Dt>Time remaining (as of last update)</Dt>
        <Dd>{formatExplorationDuration(activeAutomation.timer_seconds)}</Dd>
      </Dl>

      <FactionLoyaltyWarnings
        warning_notices={warning_notices}
        dismissing={submitting}
        on_dismiss={(warningId) => void handleDismissWarning(warningId)}
      />

      {renderAssistedNpc()}
      {renderActionError()}

      <Button
        label="Stop Faction Loyalty Automation"
        variant={ButtonVariant.DANGER}
        additional_css="w-full"
        disabled={submitting}
        on_click={handleStop}
      />
    </section>
  );
};

export default FactionLoyaltyAutomationStatus;
