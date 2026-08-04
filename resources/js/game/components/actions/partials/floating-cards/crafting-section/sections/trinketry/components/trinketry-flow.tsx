import React, { ReactNode } from 'react';

import TrinketCostSummary from './trinket-cost-summary';
import TrinketSelection from './trinket-selection';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useTrinketryFlow } from '../hooks/use-trinketry-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const TrinketryFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    status,
    selectedItem,
    crafting,
    canCraft,
    selectItem,
    craftItem,
  } = useTrinketryFlow();

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Trinketry"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderError = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Trinketry.'}
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

  const renderEmptyState = (): ReactNode => (
    <p>No Trinkets are currently available.</p>
  );

  const renderItemSelection = (
    currentData: NonNullable<typeof data>
  ): ReactNode => {
    if (currentData.items.length === 0) {
      return renderEmptyState();
    }

    return (
      <TrinketSelection
        items={currentData.items}
        selectedItemId={selectedItem?.id ?? null}
        onSelect={selectItem}
      />
    );
  };

  const renderCostSummary = (): ReactNode => {
    if (!selectedItem) {
      return null;
    }

    return <TrinketCostSummary item={selectedItem} />;
  };

  const renderAction = (): ReactNode => (
    <Button
      label={crafting ? 'Crafting…' : 'Craft Trinket'}
      on_click={() => void craftItem()}
      variant={ButtonVariant.PRIMARY}
      disabled={!canCraft}
    />
  );

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/trinketry"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Trinketry help (opens in a new tab)
    </a>
  );

  const renderContent = (currentData: NonNullable<typeof data>): ReactNode => (
    <>
      <CraftingSkillXpProgress xp={currentData.skill_xp} />
      <CraftingInventoryProgress
        inventory_count={currentData.inventory_count}
      />

      {renderItemSelection(currentData)}
      {renderCostSummary()}

      {renderAction()}

      {renderHelpLink()}
    </>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderError();
  }

  return (
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Trinketry</h2>

      {renderAlerts()}
      {renderContent(data)}
    </div>
  );
};

export default TrinketryFlow;
