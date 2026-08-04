import React, { ReactNode } from 'react';

import AlchemyCostSummary from './alchemy-cost-summary';
import AlchemyItemSelection from './alchemy-item-selection';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useAlchemyFlow } from '../hooks/use-alchemy-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
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
    <p>No Alchemy items are currently available.</p>
  );

  const renderItemSelection = (
    currentData: NonNullable<typeof data>
  ): ReactNode => {
    if (currentData.items.length === 0) {
      return renderEmptyState();
    }

    return (
      <AlchemyItemSelection
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

    return <AlchemyCostSummary item={selectedItem} />;
  };

  const renderAction = (): ReactNode => (
    <Button
      label={transmuting ? 'Transmuting…' : 'Transmute'}
      on_click={() => void transmuteItem()}
      variant={ButtonVariant.PRIMARY}
      disabled={!canTransmute}
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
      <h2 className="text-xl font-semibold">Alchemy</h2>

      {renderAlerts()}
      {renderContent(data)}
    </div>
  );
};

export default AlchemyFlow;
