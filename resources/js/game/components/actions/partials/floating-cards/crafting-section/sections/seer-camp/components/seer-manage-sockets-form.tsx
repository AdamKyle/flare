import React, { ReactNode } from 'react';

import SeerManageSocketsFormProps from './types/seer-manage-sockets-form-props';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import { useSeerManageSocketsFlow } from '../hooks/use-seer-manage-sockets-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerManageSocketsForm = ({
  costs,
  characterId,
  rootStatus,
  helpLink,
  onSuccess,
  onChangeAction,
}: SeerManageSocketsFormProps): ReactNode => {
  const {
    selectedItem,
    itemsApi,
    submitting,
    error,
    canSubmit,
    resultPreview,
    selectItem,
    submit,
  } = useSeerManageSocketsFlow({ characterId, onSuccess });

  const handleSelectItem = (option: DropdownItem): void => {
    selectItem(Number(option.value));
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

  const renderForm = (): ReactNode => (
    <div>
      <label id="seer-sockets-item-label" className="block font-semibold">
        Item
      </label>
      <Dropdown
        aria_labelled_by="seer-sockets-item-label"
        items={itemsApi.items}
        on_select={handleSelectItem}
        selection_placeholder={
          itemsApi.loading ? 'Loading items…' : 'Select an item'
        }
        searchable
        search_value={itemsApi.searchText}
        on_search={itemsApi.setSearchText}
        can_load_more={itemsApi.canLoadMore}
        is_loading_more={itemsApi.isLoadingMore}
        on_end_reached={itemsApi.onEndReached}
        empty_message="No socketable items are available."
        disabled={itemsApi.loading}
      />
    </div>
  );

  const renderPreview = (): ReactNode => {
    if (!selectedItem) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Socket preview"
        description="The exact result is random. Existing sockets are not removed."
      >
        <CraftingItemPreview item={selectedItem.preview} />
        <p>Current socket count: {selectedItem.current_sockets}</p>
        <p>
          Possible resulting sockets: {selectedItem.possible_socket_minimum}–
          {selectedItem.possible_socket_maximum}
        </p>
        <p>Cost: {costs.socket} Gold Bars.</p>
      </CraftingActionPreview>
    );
  };

  const renderResult = (): ReactNode => {
    if (!resultPreview) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>The sockets were updated.</span>
        <div className="mt-2">
          <CraftingItemPreview item={resultPreview} />
        </div>
      </Alert>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="flex flex-col gap-2 sm:flex-row">
      <Button
        label={submitting ? 'Creating…' : 'Create/ReRoll Sockets'}
        on_click={() => void submit()}
        variant={ButtonVariant.PRIMARY}
        disabled={!canSubmit}
        additional_css="w-full sm:w-auto"
      />
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
          Seer Camp: Manage Sockets
        </h2>
      }
      status={renderStatus()}
      form={renderForm()}
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={helpLink}
    />
  );
};

export default SeerManageSocketsForm;
