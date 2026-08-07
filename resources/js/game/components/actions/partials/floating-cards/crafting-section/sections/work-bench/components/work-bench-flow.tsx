import React, { ReactNode } from 'react';

import HolyOilCostSummary from './holy-oil-cost-summary';
import HolyOilSelection from './holy-oil-selection';
import WorkBenchItemSelection from './work-bench-item-selection';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import { useWorkBenchFlow } from '../hooks/use-work-bench-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const WorkBenchFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    status,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    selectedTargetSlotId,
    selectedAlchemySlotId,
    selectedTarget,
    cost,
    statBonusRange,
    devoidanceRange,
    resultPreview,
    submitting,
    canSubmit,
    itemsApi,
    oilsApi,
    selectTarget,
    selectOil,
    submitApply,
  } = useWorkBenchFlow();

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Work Bench items"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderMissingDataState = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load the Work Bench.'}
    </Alert>
  );

  const renderStatus = (): ReactNode => {
    if (!error && !mutationError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
    );
  };

  const renderEmptyItemsState = (): ReactNode => (
    <p>No valid items in your inventory to apply oils to.</p>
  );

  const renderEmptyOilsState = (): ReactNode => (
    <p>No Holy oils to apply. Craft some using alchemy!</p>
  );

  const renderItemSelection = (
    currentData: NonNullable<typeof data>
  ): ReactNode => {
    if (currentData.items.length === 0 && !itemsApi.loading) {
      return renderEmptyItemsState();
    }

    return (
      <WorkBenchItemSelection
        items={itemsApi.items}
        selectedSlotId={selectedTargetSlotId}
        loading={itemsApi.loading}
        isLoadingMore={itemsApi.isLoadingMore}
        canLoadMore={itemsApi.canLoadMore}
        searchText={itemsApi.searchText}
        onSearch={itemsApi.setSearchText}
        onEndReached={itemsApi.onEndReached}
        onSelect={selectTarget}
      />
    );
  };

  const renderOilSelection = (
    currentData: NonNullable<typeof data>
  ): ReactNode => {
    if (currentData.alchemy_items.length === 0 && !oilsApi.loading) {
      return renderEmptyOilsState();
    }

    return (
      <HolyOilSelection
        items={oilsApi.items}
        selectedSlotId={selectedAlchemySlotId}
        loading={oilsApi.loading}
        isLoadingMore={oilsApi.isLoadingMore}
        canLoadMore={oilsApi.canLoadMore}
        searchText={oilsApi.searchText}
        onSearch={oilsApi.setSearchText}
        onEndReached={oilsApi.onEndReached}
        onSelect={selectOil}
      />
    );
  };

  const renderForm = (): ReactNode => {
    if (!data) {
      return null;
    }

    return (
      <div className="space-y-3">
        {renderItemSelection(data)}
        {renderOilSelection(data)}
      </div>
    );
  };

  const renderPreview = (): ReactNode => {
    if (resultPreview) {
      return (
        <CraftingActionPreview
          title="Holy Oil application preview"
          status="success"
        >
          <p
            role="status"
            aria-live="polite"
            className="text-sm text-emerald-700 dark:text-emerald-400"
          >
            {status ?? 'The Holy Oil was applied.'}
          </p>
          <CraftingItemPreview item={resultPreview} />
        </CraftingActionPreview>
      );
    }

    if (!selectedTarget) {
      return null;
    }

    return (
      <CraftingActionPreview title="Holy Oil application preview">
        <CraftingItemPreview item={selectedTarget.preview} />
        <HolyOilCostSummary
          currentStacks={selectedTarget.preview.holy_stacks_applied}
          resultingStacks={selectedTarget.preview.holy_stacks_applied + 1}
          maximumStacks={selectedTarget.preview.holy_stacks}
          goldDustCost={cost}
          statBonusRange={statBonusRange}
          devoidanceRange={devoidanceRange}
        />
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <CraftingProgressActionButton
      idle_label="Apply Holy Oil"
      submitting_label="Applying…"
      timeout_label="Apply Holy Oil again"
      submitting={submitting}
      is_timeout_active={isTimeoutActive}
      progress={progress}
      formatted_remaining={formattedRemaining}
      disabled={!canSubmit || isCraftingDisabled}
      on_click={() => void submitApply()}
    />
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderMissingDataState();
  }

  return (
    <CraftingActionLayout
      title="Work Bench"
      status={renderStatus()}
      form={renderForm()}
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/holy-items"
      help_label="Holy Items help"
    />
  );
};

export default WorkBenchFlow;
