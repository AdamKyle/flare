import React, { ReactNode } from 'react';

import SeerManageSocketsFormProps from './types/seer-manage-sockets-form-props';
import CraftingActionButton from '../../../shared/components/crafting-action-button';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import { useSeerManageSocketsFlow } from '../hooks/use-seer-manage-sockets-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerManageSocketsForm = ({
  costs,
  characterId,
  status,
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
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
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
    if (resultPreview) {
      return (
        <CraftingActionPreview title="Socket preview" status="success">
          <p
            role="status"
            aria-live="polite"
            className="text-sm text-emerald-700 dark:text-emerald-400"
          >
            {status ?? 'The sockets were updated.'}
          </p>
          <CraftingItemPreview item={resultPreview} />
        </CraftingActionPreview>
      );
    }

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

  const renderAction = (): ReactNode => (
    <div className="space-y-2">
      <CraftingActionButton
        label={submitting ? 'Creating…' : 'Create/ReRoll Sockets'}
        on_click={() => void submit()}
        disabled={!canSubmit}
      />
      <CraftingActionButton
        label="Change Action"
        on_click={onChangeAction}
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );

  return (
    <CraftingActionLayout
      title="Seer Camp: Manage Sockets"
      status={renderStatus()}
      form={renderForm()}
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/seer-camp"
      help_label="Seer Camp help"
    />
  );
};

export default SeerManageSocketsForm;
