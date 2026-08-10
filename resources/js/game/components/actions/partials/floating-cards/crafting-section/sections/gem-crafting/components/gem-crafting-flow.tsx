import React, { ReactNode } from 'react';

import GemTierCostSummary from './gem-tier-cost-summary';
import GemTierSelection from './gem-tier-selection';
import GemDetailsContent from '../../../../../../../../reusable-components/gem/gem-details-content';
import { useOpenCharacterGemBag } from '../../../../../../../character-sheet/partials/character-inventory/hooks/use-open-character-gem-bag';
import { getGemSlotTitleTextColor } from '../../../../../../../character-sheet/partials/character-inventory/styles/gem-slot-styles';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingResultNameButton from '../../../shared/components/crafting-result-name-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useGemCraftingFlow } from '../hooks/use-gem-crafting-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const GemCraftingFlow = (): ReactNode => {
  const {
    characterId,
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
    craftedGemPreview,
    selectTier,
    craftGem,
  } = useGemCraftingFlow();

  const { openGemBag } = useOpenCharacterGemBag({
    character_id: characterId,
  });

  const handleViewCraftedGem = (): void => {
    if (!craftedGemPreview) {
      return;
    }

    openGemBag(craftedGemPreview);
  };

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
    if (status && craftSucceeded && craftedGemPreview) {
      const gemColor = getGemSlotTitleTextColor(craftedGemPreview);

      return (
        <CraftingActionPreview title="Gem preview" status="success">
          <p
            role="status"
            aria-live="polite"
            className="text-sm text-emerald-700 dark:text-emerald-400"
          >
            {'You crafted '}
            <span className={gemColor}>{craftedGemPreview.name}</span>
            {` (Tier ${craftedGemPreview.tier}).`}
          </p>
          <CraftingResultNameButton
            name={craftedGemPreview.name}
            class_name={gemColor}
            on_click={handleViewCraftedGem}
          />
          <GemDetailsContent gem={craftedGemPreview} />
        </CraftingActionPreview>
      );
    }

    if (status && !craftSucceeded) {
      return (
        <CraftingActionPreview title="Gem preview" status="danger">
          <p className="text-sm text-rose-600 dark:text-rose-400">{status}</p>
        </CraftingActionPreview>
      );
    }

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
      title="Gem Crafting"
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
      action={renderAction()}
      help_href="/information/gem-crafting"
      help_label="Gem Crafting help"
    />
  );
};

export default GemCraftingFlow;
