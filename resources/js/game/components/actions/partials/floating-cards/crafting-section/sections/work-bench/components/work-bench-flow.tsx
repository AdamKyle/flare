import React, { ReactNode } from 'react';

import HolyOilCostSummary from './holy-oil-cost-summary';
import HolyOilSelection from './holy-oil-selection';
import WorkBenchItemSelection from './work-bench-item-selection';
import { useWorkBenchFlow } from '../hooks/use-work-bench-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
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
    selectedTargetSlotId,
    selectedAlchemySlotId,
    selectedTarget,
    cost,
    submitting,
    canSubmit,
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

  const renderAlerts = (): ReactNode => {
    if (!error && !mutationError && !status) {
      return null;
    }

    return (
      <>
        {(error || mutationError) && (
          <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
        )}
        {status && <Alert variant={AlertVariant.SUCCESS}>{status}</Alert>}
      </>
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
    if (currentData.items.length === 0) {
      return renderEmptyItemsState();
    }

    return (
      <WorkBenchItemSelection
        items={currentData.items}
        selectedSlotId={selectedTargetSlotId}
        onSelect={selectTarget}
      />
    );
  };

  const renderOilSelection = (
    currentData: NonNullable<typeof data>
  ): ReactNode => {
    if (currentData.alchemy_items.length === 0) {
      return renderEmptyOilsState();
    }

    return (
      <HolyOilSelection
        oils={currentData.alchemy_items}
        selectedSlotId={selectedAlchemySlotId}
        onSelect={selectOil}
      />
    );
  };

  const renderCostSummary = (): ReactNode => {
    if (!selectedTarget) {
      return null;
    }

    return (
      <HolyOilCostSummary
        currentStacks={selectedTarget.item.holy_stacks_applied}
        maximumStacks={selectedTarget.item.holy_stacks}
        goldDustCost={cost}
      />
    );
  };

  const renderAction = (): ReactNode => (
    <Button
      label={submitting ? 'Applying…' : 'Apply Holy Oil'}
      on_click={() => void submitApply()}
      variant={ButtonVariant.PRIMARY}
      disabled={!canSubmit}
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
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Work Bench</h2>

      {renderAlerts()}

      {renderItemSelection(data)}
      {renderOilSelection(data)}
      {renderCostSummary()}

      {renderAction()}

      {renderHelpLink()}
    </div>
  );
};

export default WorkBenchFlow;
