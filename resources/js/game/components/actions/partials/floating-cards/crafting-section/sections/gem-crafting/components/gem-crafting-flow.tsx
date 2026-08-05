import React, { ReactNode } from 'react';

import GemTierCostSummary from './gem-tier-cost-summary';
import GemTierSelection from './gem-tier-selection';
import { getGemSlotTitleTextColor } from '../../../../../../../character-sheet/partials/character-inventory/styles/gem-slot-styles';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useGemCraftingFlow } from '../hooks/use-gem-crafting-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const GemCraftingFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    status,
    selectedTier,
    selectedTierData,
    crafting,
    canCraft,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    craftSucceeded,
    craftedGem,
    selectTier,
    craftGem,
  } = useGemCraftingFlow();

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Gem Crafting"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderMissingDataState = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Gem Crafting.'}
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

  const renderPreview = (): ReactNode => {
    if (!selectedTierData) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Gem preview"
        description="The exact Gem and its atonements are generated after a successful craft."
      >
        <GemTierCostSummary tier={selectedTierData} />
      </CraftingActionPreview>
    );
  };

  const renderResult = (): ReactNode => {
    if (!status) {
      return null;
    }

    if (!craftSucceeded || !craftedGem) {
      return <Alert variant={AlertVariant.DANGER}>{status}</Alert>;
    }

    const gemColor = getGemSlotTitleTextColor(craftedGem);

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>
          {'You crafted '}
          <span className={gemColor}>{craftedGem.name}</span>
          {` (Tier ${craftedGem.tier}).`}
        </span>
      </Alert>
    );
  };

  const renderAction = (): ReactNode => (
    <CraftingProgressActionButton
      idle_label="Craft Gem"
      submitting_label="Crafting…"
      timeout_label="Craft another Gem"
      submitting={crafting}
      is_timeout_active={isTimeoutActive}
      progress={progress}
      formatted_remaining={formattedRemaining}
      disabled={!canCraft || isCraftingDisabled}
      on_click={() => void craftGem()}
      variant={ButtonVariant.PRIMARY}
      additional_css="w-full sm:w-auto"
    />
  );

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/gem-crafting"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Gem Crafting help (opens in a new tab)
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
          Gem Crafting
        </h2>
      }
      status={renderStatus()}
      progress={
        <div className="space-y-2">
          <CraftingSkillXpProgress xp={data.skill_xp} />
          <CraftingInventoryProgress inventory_count={data.inventory_count} />
        </div>
      }
      form={
        <GemTierSelection
          tiers={data.tiers}
          selectedTier={selectedTier}
          onSelect={selectTier}
        />
      }
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={renderHelpLink()}
    />
  );
};

export default GemCraftingFlow;
