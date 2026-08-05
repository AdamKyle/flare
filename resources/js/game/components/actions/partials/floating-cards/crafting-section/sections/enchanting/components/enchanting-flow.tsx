import React, { ReactNode } from 'react';

import EnchantingAffixSelection from './enchanting-affix-selection';
import EnchantingCostSummary from './enchanting-cost-summary';
import EnchantingItemSelection from './enchanting-item-selection';
import EnchantingSourceSelection from './enchanting-source-selection';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useEnchantingFlow } from '../hooks/use-enchanting-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const ENCHANT_FAILED_MESSAGE =
  'The enchantment failed. Check Server Messages for the full outcome.';

const EnchantingFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    hasEventChoice,
    effectiveSource,
    effectiveSlotId,
    totalCost,
    submitting,
    canSubmit,
    lastEnchantSucceeded,
    resultPreview,
    itemsApi,
    prefixApi,
    suffixApi,
    selectSource,
    selectSlot,
    selectPrefix,
    selectSuffix,
    submitEnchant,
  } = useEnchantingFlow();

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading enchanting options"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderEmptyState = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Enchanting.'}
    </Alert>
  );

  const renderError = (): ReactNode => {
    if (!error && !mutationError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
    );
  };

  const renderXpProgress = (): ReactNode => {
    if (!data) {
      return null;
    }

    return <CraftingSkillXpProgress xp={data.skill_xp} />;
  };

  const renderInventoryProgress = (): ReactNode => {
    if (!data || !data.inventory_count) {
      return null;
    }

    return <CraftingInventoryProgress inventory_count={data.inventory_count} />;
  };

  const renderSourceSelection = (): ReactNode => {
    if (!hasEventChoice || effectiveSource !== null) {
      return null;
    }

    return <EnchantingSourceSelection onSelect={selectSource} />;
  };

  const renderItemSelection = (): ReactNode => {
    if (!data || effectiveSource === null) {
      return null;
    }

    return (
      <EnchantingItemSelection
        items={itemsApi.items}
        loading={itemsApi.loading}
        isLoadingMore={itemsApi.isLoadingMore}
        canLoadMore={itemsApi.canLoadMore}
        searchText={itemsApi.searchText}
        onSearch={itemsApi.setSearchText}
        onEndReached={itemsApi.onEndReached}
        selectedSlotId={effectiveSlotId}
        onSelect={selectSlot}
      />
    );
  };

  const renderAffixSelection = (): ReactNode => {
    if (!data || effectiveSource === null) {
      return null;
    }

    return (
      <EnchantingAffixSelection
        prefix={{
          items: prefixApi.affixes,
          loading: prefixApi.loading,
          isLoadingMore: prefixApi.isLoadingMore,
          canLoadMore: prefixApi.canLoadMore,
          searchText: prefixApi.searchText,
          onSearch: prefixApi.setSearchText,
          onEndReached: prefixApi.onEndReached,
        }}
        suffix={{
          items: suffixApi.affixes,
          loading: suffixApi.loading,
          isLoadingMore: suffixApi.isLoadingMore,
          canLoadMore: suffixApi.canLoadMore,
          searchText: suffixApi.searchText,
          onSearch: suffixApi.setSearchText,
          onEndReached: suffixApi.onEndReached,
        }}
        onPrefix={selectPrefix}
        onSuffix={selectSuffix}
      />
    );
  };

  const renderForm = (): ReactNode => (
    <div className="space-y-3">
      {renderSourceSelection()}
      {renderItemSelection()}
      {renderAffixSelection()}
    </div>
  );

  const renderPreview = (): ReactNode => {
    if (effectiveSource === null) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Enchantment preview"
        description="The final result depends on the enchanting roll and is not shown until the attempt completes."
      >
        <EnchantingCostSummary totalCost={totalCost} />
      </CraftingActionPreview>
    );
  };

  const renderResult = (): ReactNode => {
    if (lastEnchantSucceeded === null) {
      return null;
    }

    if (!lastEnchantSucceeded) {
      return (
        <Alert variant={AlertVariant.DANGER}>{ENCHANT_FAILED_MESSAGE}</Alert>
      );
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>Your enchantment was applied.</span>
        {resultPreview && (
          <div className="mt-2">
            <CraftingItemPreview item={resultPreview} />
          </div>
        )}
      </Alert>
    );
  };

  const renderAction = (): ReactNode => {
    if (effectiveSource === null) {
      return null;
    }

    return (
      <CraftingProgressActionButton
        idle_label="Enchant Item"
        submitting_label="Enchanting…"
        timeout_label="Enchant again"
        submitting={submitting}
        is_timeout_active={isTimeoutActive}
        progress={progress}
        formatted_remaining={formattedRemaining}
        disabled={!canSubmit || isCraftingDisabled}
        on_click={() => void submitEnchant()}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full sm:w-auto"
      />
    );
  };

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/enchanting"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Enchanting information (opens in a new tab)
    </a>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderEmptyState();
  }

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Enchanting
        </h2>
      }
      status={renderError()}
      progress={
        <div className="space-y-2">
          {renderXpProgress()}
          {renderInventoryProgress()}
        </div>
      }
      form={renderForm()}
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={renderHelpLink()}
    />
  );
};

export default EnchantingFlow;
