import React, { ReactNode } from 'react';

import AlchemyCostSummary from './alchemy-cost-summary';
import AlchemyItemSelection from './alchemy-item-selection';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useAlchemyFlow } from '../hooks/use-alchemy-flow';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const AlchemyFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    status,
    selectedItem,
    transmuting,
    canTransmute,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    itemsApi,
    alchemyResult,
    selectItem,
    transmuteItem,
  } = useAlchemyFlow();

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Alchemy"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderError = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Alchemy.'}
    </Alert>
  );

  const renderAlerts = (): ReactNode => {
    if (!error && !mutationError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
    );
  };

  const renderItemSelection = (): ReactNode => (
    <AlchemyItemSelection
      items={itemsApi.items}
      selectedItemId={selectedItem?.id ?? null}
      loading={itemsApi.loading}
      isLoadingMore={itemsApi.isLoadingMore}
      canLoadMore={itemsApi.canLoadMore}
      searchText={itemsApi.searchText}
      onSearch={itemsApi.setSearchText}
      onEndReached={itemsApi.onEndReached}
      onSelect={selectItem}
    />
  );

  const renderPreview = (): ReactNode => {
    if (!selectedItem) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Item preview"
        description="You will attempt to create one of this item."
      >
        <p className="font-semibold text-gray-900 dark:text-gray-100">
          {selectedItem.name}
        </p>
        <p className="text-xs text-gray-500 dark:text-gray-400">
          Type: {selectedItem.type} &bull; Quantity Attempted: 1
        </p>
        <AlchemyCostSummary item={selectedItem} />
      </CraftingActionPreview>
    );
  };

  const renderResult = (): ReactNode => {
    if (!status && !alchemyResult) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        {alchemyResult ? (
          <span>
            {'You created '}
            <strong>
              {formatNumberWithCommas(alchemyResult.amount_created)}
            </strong>
            {' x '}
            {alchemyResult.name}
            {'. You now own '}
            {formatNumberWithCommas(alchemyResult.current_amount)}
            {'.'}
          </span>
        ) : (
          <span>{status}</span>
        )}
      </Alert>
    );
  };

  const renderAction = (): ReactNode => (
    <CraftingProgressActionButton
      idle_label="Transmute"
      submitting_label="Transmuting…"
      timeout_label="Transmute again"
      submitting={transmuting}
      is_timeout_active={isTimeoutActive}
      progress={progress}
      formatted_remaining={formattedRemaining}
      disabled={!canTransmute || isCraftingDisabled}
      on_click={() => void transmuteItem()}
      variant={ButtonVariant.PRIMARY}
      additional_css="w-full sm:w-auto"
    />
  );

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/alchemy"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Alchemy help (opens in a new tab)
    </a>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderError();
  }

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Alchemy
        </h2>
      }
      status={renderAlerts()}
      progress={
        <div className="space-y-2">
          <CraftingSkillXpProgress xp={data.skill_xp} />
          <CraftingInventoryProgress inventory_count={data.inventory_count} />
        </div>
      }
      form={renderItemSelection()}
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={renderHelpLink()}
    />
  );
};

export default AlchemyFlow;
