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
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
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

  const renderResult = (): ReactNode => {
    if (!status) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>{status}</span>
        {resultPreview && (
          <div className="mt-2">
            <CraftingItemPreview item={resultPreview} />
          </div>
        )}
      </Alert>
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
      variant={ButtonVariant.PRIMARY}
      additional_css="w-full sm:w-auto"
    />
  );

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/holy-items"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Holy Items help (opens in a new tab)
    </a>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderMissingDataState();
  }

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Work Bench
        </h2>
      }
      status={renderStatus()}
      form={renderForm()}
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={renderHelpLink()}
    />
  );
};

export default WorkBenchFlow;
