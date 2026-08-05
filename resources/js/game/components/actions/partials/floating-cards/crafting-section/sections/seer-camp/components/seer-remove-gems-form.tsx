import React, { ReactNode } from 'react';

import SeerRemoveGemsFormProps from './types/seer-remove-gems-form-props';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
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
  rootStatus,
  helpLink,
  onSuccess,
  onChangeAction,
}: SeerRemoveGemsFormProps): ReactNode => {
  const {
    gemId,
    selectedItem,
    selectedDetails,
    selectedChange,
    itemsApi,
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

  const renderStatus = (): ReactNode => {
    if (!rootStatus && !error) {
      return null;
    }

    return (
      <div className="space-y-2">
        {rootStatus}
        {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}
      </div>
    );
  };

  const renderEmptyItemsState = (): ReactNode => {
    if (removalData?.items.length) {
      return null;
    }

    return <p>No socketed items are available.</p>;
  };

  const renderForm = (): ReactNode => (
    <div className="space-y-4">
      <label id="seer-remove-item-label" className="block font-semibold">
        Item with Gems
      </label>
      <Dropdown
        aria_labelled_by="seer-remove-item-label"
        items={itemsApi.items}
        on_select={handleSelectItem}
        on_clear={clearItem}
        selection_placeholder={
          itemsApi.loading ? 'Loading items…' : 'Select an item'
        }
        searchable
        search_value={itemsApi.searchText}
        on_search={itemsApi.setSearchText}
        can_load_more={itemsApi.canLoadMore}
        is_loading_more={itemsApi.isLoadingMore}
        on_end_reached={itemsApi.onEndReached}
        empty_message="No items with Gems are available."
        disabled={isSubmitting || itemsApi.loading}
      />

      {renderEmptyItemsState()}

      {selectedItem && selectedDetails && (
        <>
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
        </>
      )}
    </div>
  );

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

  const renderPreview = (): ReactNode => {
    if (!selectedItem || !selectedDetails) {
      return null;
    }

    return (
      <CraftingActionPreview
        title={selectedItem.preview.name}
        description="Removal preview"
      >
        <h4 className="font-semibold">Original atonement</h4>
        {renderAtonement(selectedDetails.comparison.original_atonement)}
        <p>
          Remove one cost: {selectedDetails.remove_one_cost} Gold Bars. Remove
          all cost: {selectedDetails.remove_all_cost} Gold Bars.
        </p>
        {renderChangeResult()}
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="flex flex-col gap-2 sm:flex-row">
      {selectedItem && selectedDetails && (
        <>
          <Button
            label={isRemovingOne ? 'Removing…' : 'Remove Gem'}
            on_click={() => void removeOne()}
            variant={ButtonVariant.PRIMARY}
            disabled={!gemId || isSubmitting}
            additional_css="w-full sm:w-auto"
          />
          <Button
            label={isRemovingAll ? 'Removing all…' : 'Remove All Gems'}
            on_click={() => void removeAll()}
            variant={ButtonVariant.DANGER}
            disabled={selectedDetails.gems.length === 0 || isSubmitting}
            additional_css="w-full sm:w-auto"
          />
        </>
      )}
      <Button
        label="Change Action"
        on_click={onChangeAction}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full sm:w-auto"
      />
    </div>
  );

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Seer Camp: Remove Gems
        </h2>
      }
      status={renderStatus()}
      form={renderForm()}
      preview={renderPreview()}
      action={renderAction()}
      help_link={helpLink}
    />
  );
};

export default SeerRemoveGemsForm;
