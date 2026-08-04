import React, { ReactNode } from 'react';

import EnchantingAffixSelection from './enchanting-affix-selection';
import EnchantingCostSummary from './enchanting-cost-summary';
import EnchantingItemSelection from './enchanting-item-selection';
import EnchantingSourceSelection from './enchanting-source-selection';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useEnchantingFlow } from '../hooks/use-enchanting-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const ENCHANT_SUCCEEDED_MESSAGE =
  'Your enchantment was applied. Check Server Messages for the full outcome.';
const ENCHANT_FAILED_MESSAGE =
  'The enchantment failed. Check Server Messages for the full outcome.';

const EnchantingFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    isCraftingDisabled,
    hasEventChoice,
    effectiveSource,
    effectiveSlotId,
    selectedPrefixId,
    selectedSuffixId,
    totalCost,
    submitting,
    canSubmit,
    lastEnchantSucceeded,
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

  const renderStatus = (): ReactNode => {
    if (lastEnchantSucceeded === null) {
      return null;
    }

    const variant = lastEnchantSucceeded
      ? AlertVariant.SUCCESS
      : AlertVariant.DANGER;

    const message = lastEnchantSucceeded
      ? ENCHANT_SUCCEEDED_MESSAGE
      : ENCHANT_FAILED_MESSAGE;

    return <Alert variant={variant}>{message}</Alert>;
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
        regularItems={data.affixes.character_inventory}
        eventItems={data.affixes.items_for_event}
        source={effectiveSource}
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
        affixes={data.affixes.affixes}
        selectedPrefixId={selectedPrefixId}
        selectedSuffixId={selectedSuffixId}
        onPrefix={selectPrefix}
        onSuffix={selectSuffix}
      />
    );
  };

  const renderCostSummary = (): ReactNode => {
    if (effectiveSource === null) {
      return null;
    }

    return <EnchantingCostSummary totalCost={totalCost} />;
  };

  const renderAction = (): ReactNode => {
    if (effectiveSource === null) {
      return null;
    }

    return (
      <Button
        label={submitting ? 'Enchanting…' : 'Enchant Item'}
        on_click={() => void submitEnchant()}
        variant={ButtonVariant.PRIMARY}
        disabled={!canSubmit || submitting || isCraftingDisabled}
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

  const renderContent = (): ReactNode => (
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Enchanting</h2>

      {renderError()}
      {renderStatus()}

      {renderXpProgress()}
      {renderInventoryProgress()}

      {renderSourceSelection()}
      {renderItemSelection()}
      {renderAffixSelection()}
      {renderCostSummary()}
      {renderAction()}
      {renderHelpLink()}
    </div>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderEmptyState();
  }

  return renderContent();
};

export default EnchantingFlow;
