import React, { ReactNode } from 'react';

import SeerRemoveGemsFormProps from './types/seer-remove-gems-form-props';
import { ElementalAtonementDefinition } from '../api/definitions/gem-comparison-api-response-definition';
import { useSeerRemoveGemsFlow } from '../hooks/use-seer-remove-gems-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const renderAtonement = (
  atonement: ElementalAtonementDefinition
): ReactNode => (
  <div className="space-y-2">
    <dl className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
      {Object.entries(atonement.atonements).map(([name, amount]) => (
        <div key={name}>
          <dt className="font-semibold">{name}</dt>
          <dd>{amount}</dd>
        </div>
      ))}
    </dl>
    <p>
      <span className="font-semibold">Strongest elemental damage:</span>{' '}
      {atonement.elemental_damage.name} ({atonement.elemental_damage.amount})
    </p>
  </div>
);

const SeerRemoveGemsForm = ({
  removalData,
  characterId,
  onSuccess,
}: SeerRemoveGemsFormProps): ReactNode => {
  const {
    gemId,
    selectedItem,
    selectedDetails,
    selectedChange,
    itemOptions,
    gemOptions,
    isRemovingOne,
    isRemovingAll,
    isSubmitting,
    error,
    selectItem,
    clearItem,
    selectGem,
    clearGem,
    removeOne,
    removeAll,
  } = useSeerRemoveGemsFlow({ removalData, characterId, onSuccess });

  const handleSelectItem = (option: DropdownItem): void => {
    selectItem(Number(option.value));
  };

  const handleSelectGem = (option: DropdownItem): void => {
    selectGem(Number(option.value));
  };

  const renderErrors = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderEmptyItemsState = (): ReactNode => {
    if (removalData?.items.length) {
      return null;
    }

    return <p>No socketed items are available.</p>;
  };

  const renderChangeResult = (): ReactNode => {
    if (!selectedChange) {
      return null;
    }

    return (
      <section className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
        <h4 className="font-semibold">
          Result after removing the selected Gem
        </h4>
        {renderAtonement(selectedChange.comparisons)}
      </section>
    );
  };

  const renderRemovalDetails = (): ReactNode => {
    if (!selectedItem || !selectedDetails) {
      return null;
    }

    return (
      <section className="space-y-4" aria-labelledby="seer-removal-heading">
        <h3 id="seer-removal-heading" className="text-lg font-semibold">
          {selectedItem.name}
        </h3>

        <section className="space-y-2">
          <h4 className="font-semibold">Original atonement</h4>
          {renderAtonement(selectedDetails.comparison.original_atonement)}
        </section>

        <p>
          Remove one cost: {selectedDetails.remove_one_cost} Gold Bars. Remove
          all cost: {selectedDetails.remove_all_cost} Gold Bars.
        </p>

        <label id="seer-remove-gem-label" className="block font-semibold">
          Attached Gem
        </label>
        <Dropdown
          aria_labelled_by="seer-remove-gem-label"
          items={gemOptions}
          on_select={handleSelectGem}
          on_clear={clearGem}
          selection_placeholder="Select an attached Gem"
          disabled={isSubmitting}
        />

        {selectedDetails.gems.length === 0 && (
          <p>No Gems are attached to this item.</p>
        )}

        {renderChangeResult()}

        <div className="flex flex-col gap-3 sm:flex-row">
          <Button
            label={isRemovingOne ? 'Removing…' : 'Remove Gem'}
            on_click={() => void removeOne()}
            variant={ButtonVariant.PRIMARY}
            disabled={!gemId || isSubmitting}
          />
          <Button
            label={isRemovingAll ? 'Removing all…' : 'Remove All Gems'}
            on_click={() => void removeAll()}
            variant={ButtonVariant.DANGER}
            disabled={selectedDetails.gems.length === 0 || isSubmitting}
          />
        </div>
      </section>
    );
  };

  return (
    <div className="space-y-4">
      {renderErrors()}

      <label id="seer-remove-item-label" className="block font-semibold">
        Item with Gems
      </label>
      <Dropdown
        aria_labelled_by="seer-remove-item-label"
        items={itemOptions}
        on_select={handleSelectItem}
        on_clear={clearItem}
        selection_placeholder="Select an item"
        disabled={isSubmitting}
      />

      {renderEmptyItemsState()}
      {renderRemovalDetails()}
    </div>
  );
};

export default SeerRemoveGemsForm;
