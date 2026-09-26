import clsx from 'clsx';
import React, { ReactNode, useId } from 'react';

import DelveCurrentFoeSection from './delve-current-foe-section';
import DelveQuestItemsSection from './delve-quest-items-section';
import DelveRewardCheckpointsSection from './delve-reward-checkpoints-section';
import DelveStatusPanelProps from './types/delve-status-panel-props';
import { useOpenItemDetailsSidePeek } from '../../../../../side-peeks/item-details/hooks/use-open-item-details-side-peek';
import ExplorationDetailSection from '../../exploration/components/exploration-detail-section';
import { formatExplorationDuration } from '../../exploration/utils/format-exploration-duration';
import { useDelveActions } from '../api/hooks/use-delve-actions';
import { resolveDelveEndReasonLabel } from '../utils/resolve-delve-end-reason-label';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const DelveStatusPanel = ({
  character_id: characterId,
  status,
  on_refetch: onRefetch,
}: DelveStatusPanelProps): ReactNode => {
  const { stopping, dismissing, error, stop, dismiss } =
    useDelveActions(characterId);
  const { openItemDetails } = useOpenItemDetailsSidePeek();
  const headingId = useId();

  const stateLabel = status.completed
    ? `Ended: ${resolveDelveEndReasonLabel(status.reason)}`
    : 'Running';

  const handleStop = async () => {
    const stopped = await stop();

    if (!stopped) {
      return;
    }

    onRefetch();
  };

  const handleDismiss = async () => {
    const dismissed = await dismiss();

    if (!dismissed) {
      return;
    }

    onRefetch();
  };

  const handleViewQuestItem = (itemId: number) => {
    const questItem = status.quest_items.find(
      (candidate) => candidate.id === itemId
    );

    openItemDetails(itemId, questItem?.name ?? 'Quest Item Details');
  };

  const renderCompletedAt = (): ReactNode => {
    if (!status.completed) {
      return null;
    }

    return (
      <>
        <Dt>Ended At</Dt>
        <Dd>{status.completed_at}</Dd>
      </>
    );
  };

  const renderQuestItemCountdown = (): ReactNode => {
    if (status.completed) {
      return null;
    }

    if (status.quest_item_drop_hours_required === null) {
      return null;
    }

    const remaining = status.quest_item_drop_available
      ? 'Available now'
      : `${formatExplorationDuration(status.quest_item_drop_seconds_remaining ?? 0)} remaining`;

    return (
      <ExplorationDetailSection title="Quest Item Drop">
        <Dl>
          <Dt>Hours Required</Dt>
          <Dd>{status.quest_item_drop_hours_required}</Dd>
          <Dt>Status</Dt>
          <Dd>{remaining}</Dd>
        </Dl>
      </ExplorationDetailSection>
    );
  };

  const renderError = (): ReactNode => {
    if (error === null) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderAction = (): ReactNode => {
    if (status.completed) {
      return (
        <Button
          label="Dismiss"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full"
          disabled={dismissing}
          on_click={() => void handleDismiss()}
        />
      );
    }

    return (
      <Button
        label="Stop Delve"
        variant={ButtonVariant.DANGER}
        additional_css="w-full"
        disabled={stopping}
        on_click={() => void handleStop()}
      />
    );
  };

  return (
    <section aria-labelledby={headingId} className="space-y-3">
      <div className="flex items-center justify-between gap-3">
        <h3
          id={headingId}
          className="text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          Delve
        </h3>
        <p
          role="status"
          aria-live="polite"
          className={clsx(
            'shrink-0 text-sm font-semibold',
            status.completed
              ? 'text-gray-700 dark:text-gray-300'
              : 'text-regent-st-blue-700 dark:text-regent-st-blue-300'
          )}
        >
          {stateLabel}
        </p>
      </div>

      <ExplorationDetailSection title="Timing">
        <Dl>
          <Dt>Started At</Dt>
          <Dd>{status.started_at}</Dd>
          {renderCompletedAt()}
          <Dt>Elapsed (as of last update)</Dt>
          <Dd>{formatExplorationDuration(status.elapsed_seconds)}</Dd>
          <Dt>Enemy Strength Increase</Dt>
          <Dd>{status.increase_percentage}%</Dd>
        </Dl>
      </ExplorationDetailSection>

      {renderQuestItemCountdown()}

      <DelveCurrentFoeSection current_foe={status.current_foe} />

      <DelveQuestItemsSection
        quest_items={status.quest_items}
        on_view_quest_item={handleViewQuestItem}
      />

      <DelveRewardCheckpointsSection
        reward_checkpoints={status.reward_checkpoints}
      />

      {renderError()}
      {renderAction()}
    </section>
  );
};

export default DelveStatusPanel;
