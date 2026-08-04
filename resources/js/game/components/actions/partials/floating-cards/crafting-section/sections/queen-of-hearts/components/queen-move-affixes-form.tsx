import React, { ReactNode } from 'react';

import QueenCostSummary from './queen-cost-summary';
import QueenMoveAffixesFormProps from './types/queen-move-affixes-form-props';
import { useQueenMoveAffixesFlow } from '../hooks/use-queen-move-affixes-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';

const QueenMoveAffixesForm = ({
  data,
  characterId,
  onSuccess,
  onDataReplaced,
}: QueenMoveAffixesFormProps): ReactNode => {
  const {
    hasSourceSlots,
    sourceOptions,
    destinationOptions,
    affixOptions,
    selectedSourceId,
    selectedCost,
    submitting,
    error,
    canSubmit,
    handleSelectSource,
    handleSelectAffix,
    handleSelectDestination,
    handleSubmit,
  } = useQueenMoveAffixesFlow({ characterId, data, onDataReplaced, onSuccess });

  const renderError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderEmptyState = (): ReactNode => {
    if (hasSourceSlots) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        You do not have any unique items with affixes to move.
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
    if (!hasSourceSlots) {
      return null;
    }

    return (
      <div className="space-y-4">
        <label id="queen-move-source-label" className="block font-semibold">
          Source item
        </label>
        <Dropdown
          aria_labelled_by="queen-move-source-label"
          items={sourceOptions}
          selection_placeholder="Select the source item"
          on_select={handleSelectSource}
        />

        <label id="queen-move-affixes-label" className="block font-semibold">
          Affixes to move
        </label>
        <Dropdown
          aria_labelled_by="queen-move-affixes-label"
          items={affixOptions}
          selection_placeholder="Select Prefix, Suffix, or Both"
          disabled={!selectedSourceId}
          on_select={handleSelectAffix}
        />

        <label
          id="queen-move-destination-label"
          className="block font-semibold"
        >
          Destination item
        </label>
        <Dropdown
          aria_labelled_by="queen-move-destination-label"
          items={destinationOptions}
          selection_placeholder="Select the destination item"
          disabled={!selectedSourceId}
          on_select={handleSelectDestination}
        />

        {renderCostSummary()}

        <Button
          label={submitting ? 'Moving…' : 'Move Enchants'}
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

export default QueenMoveAffixesForm;
