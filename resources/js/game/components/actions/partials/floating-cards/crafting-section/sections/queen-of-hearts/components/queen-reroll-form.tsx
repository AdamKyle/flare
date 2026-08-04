import React, { ReactNode } from 'react';

import QueenCostSummary from './queen-cost-summary';
import QueenRerollFormProps from './types/queen-reroll-form-props';
import { useQueenRerollFlow } from '../hooks/use-queen-reroll-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';

const QueenRerollForm = ({
  data,
  characterId,
  onSuccess,
  onDataReplaced,
}: QueenRerollFormProps): ReactNode => {
  const {
    hasSlots,
    slotOptions,
    affixOptions,
    rerollTypeOptions,
    selectedSlotId,
    selectedAffix,
    selectedCost,
    submitting,
    error,
    canSubmit,
    handleSelectSlot,
    handleSelectAffix,
    handleSelectRerollType,
    handleSubmit,
  } = useQueenRerollFlow({ characterId, data, onDataReplaced, onSuccess });

  const renderError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderEmptyState = (): ReactNode => {
    if (hasSlots) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        You do not have any unique items to re-roll.
      </Alert>
    );
  };

  const renderCostSummary = (): ReactNode => {
    if (!selectedCost) {
      return null;
    }

    return (
      <QueenCostSummary
        goldDust={selectedCost.gold_dust}
        shards={selectedCost.shards}
      />
    );
  };

  const renderForm = (): ReactNode => {
    if (!hasSlots) {
      return null;
    }

    return (
      <div className="space-y-4">
        <label id="queen-reroll-item-label" className="block font-semibold">
          Unique item
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-item-label"
          items={slotOptions}
          selection_placeholder="Select a unique item"
          on_select={handleSelectSlot}
        />

        <label id="queen-reroll-affix-label" className="block font-semibold">
          Affix
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-affix-label"
          items={affixOptions}
          selection_placeholder="Select Prefix, Suffix, or Both"
          disabled={!selectedSlotId}
          on_select={handleSelectAffix}
        />

        <label id="queen-reroll-category-label" className="block font-semibold">
          Reroll category
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-category-label"
          items={rerollTypeOptions}
          selection_placeholder="Select a reroll category"
          disabled={!selectedAffix}
          on_select={handleSelectRerollType}
        />

        {renderCostSummary()}

        <Button
          label={submitting ? 'Re rolling…' : 'Re roll'}
          on_click={() => void handleSubmit()}
          variant={ButtonVariant.PRIMARY}
          disabled={!canSubmit}
        />
      </div>
    );
  };

  return (
    <div className="space-y-4">
      {renderError()}
      {renderEmptyState()}
      {renderForm()}
    </div>
  );
};

export default QueenRerollForm;
