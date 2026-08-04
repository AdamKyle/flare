import React, { ReactNode } from 'react';

import SeerGemComparison from './seer-gem-comparison';
import SeerReplaceGemForm from './seer-replace-gem-form';
import SeerAttachGemFormProps from './types/seer-attach-gem-form-props';
import { useSeerAttachGemFlow } from '../hooks/use-seer-attach-gem-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerAttachGemForm = ({
  items,
  gems,
  costs,
  characterId,
  onSuccess,
}: SeerAttachGemFormProps): ReactNode => {
  const {
    comparison,
    comparisonLoading,
    error,
    addSubmitting,
    replaceSubmitting,
    canReplace,
    replaceId,
    itemOptions,
    gemOptions,
    selectSlot,
    selectGemSlot,
    selectReplaceGem,
    addGem,
    replaceGem,
  } = useSeerAttachGemFlow({ characterId, items, gems, onSuccess });

  const handleSelectItem = (option: DropdownItem): void => {
    selectSlot(Number(option.value));
  };

  const handleSelectGem = (option: DropdownItem): void => {
    selectGemSlot(Number(option.value));
  };

  const renderErrors = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderComparisonLoading = (): ReactNode => {
    if (!comparisonLoading) {
      return null;
    }

    return <p role="status">Loading comparison…</p>;
  };

  const renderReplaceForm = (): ReactNode => {
    if (!comparison || comparison.attached_gems.length === 0) {
      return null;
    }

    return (
      <SeerReplaceGemForm
        attachedGems={comparison.attached_gems}
        selectedGemId={replaceId}
        replaceCost={costs.replace}
        submitting={!canReplace || replaceSubmitting}
        onSelect={selectReplaceGem}
        onSubmit={() => void replaceGem()}
      />
    );
  };

  const renderComparison = (): ReactNode => {
    if (!comparison) {
      return null;
    }

    return (
      <>
        <SeerGemComparison comparison={comparison} />
        <p>Attach Gem cost: {costs.attach} Gold Bars.</p>
        <Button
          label="Add Gem"
          on_click={() => void addGem()}
          variant={ButtonVariant.PRIMARY}
          disabled={addSubmitting}
        />
        {renderReplaceForm()}
      </>
    );
  };

  return (
    <div className="space-y-4">
      {renderErrors()}

      <label id="seer-attach-item-label" className="block font-semibold">
        Item
      </label>
      <Dropdown
        aria_labelled_by="seer-attach-item-label"
        items={itemOptions}
        on_select={handleSelectItem}
        selection_placeholder="Select an item"
      />

      <label id="seer-attach-gem-label" className="block font-semibold">
        Gem
      </label>
      <Dropdown
        aria_labelled_by="seer-attach-gem-label"
        items={gemOptions}
        on_select={handleSelectGem}
        selection_placeholder="Select a Gem"
      />

      {renderComparisonLoading()}
      {renderComparison()}
    </div>
  );
};

export default SeerAttachGemForm;
