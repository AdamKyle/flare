import React, { ReactNode } from 'react';

import GemTierCostSummary from './gem-tier-cost-summary';
import GemTierSelection from './gem-tier-selection';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useGemCraftingFlow } from '../hooks/use-gem-crafting-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
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

  const renderAlerts = (): ReactNode => {
    if (!error && !mutationError && !status) {
      return null;
    }

    return (
      <>
        {(error || mutationError) && (
          <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
        )}
        {status && <Alert variant={AlertVariant.INFO}>{status}</Alert>}
      </>
    );
  };

  const renderCostSummary = (): ReactNode => {
    if (!selectedTierData) {
      return null;
    }

    return <GemTierCostSummary tier={selectedTierData} />;
  };

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

  const renderCraftingContent = (
    currentData: NonNullable<typeof data>
  ): ReactNode => (
    <>
      <CraftingSkillXpProgress xp={currentData.skill_xp} />
      <CraftingInventoryProgress
        inventory_count={currentData.inventory_count}
      />

      <GemTierSelection
        tiers={currentData.tiers}
        selectedTier={selectedTier}
        onSelect={selectTier}
      />

      {renderCostSummary()}

      <Button
        label={crafting ? 'Crafting…' : 'Craft Gem'}
        on_click={() => void craftGem()}
        variant={ButtonVariant.PRIMARY}
        disabled={!canCraft}
      />

      {renderHelpLink()}
    </>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderMissingDataState();
  }

  return (
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Gem Crafting</h2>

      {renderAlerts()}
      {renderCraftingContent(data)}
    </div>
  );
};

export default GemCraftingFlow;
