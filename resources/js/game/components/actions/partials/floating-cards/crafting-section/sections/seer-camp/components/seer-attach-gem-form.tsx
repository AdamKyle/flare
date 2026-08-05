import React, { ReactNode } from 'react';

import SeerGemComparison from './seer-gem-comparison';
import SeerReplaceGemForm from './seer-replace-gem-form';
import SeerAttachGemFormProps from './types/seer-attach-gem-form-props';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import { useSeerAttachGemFlow } from '../hooks/use-seer-attach-gem-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerAttachGemForm = ({
  costs,
  characterId,
  rootStatus,
  helpLink,
  onSuccess,
  onChangeAction,
}: SeerAttachGemFormProps): ReactNode => {
  const {
    comparison,
    comparisonLoading,
    error,
    addSubmitting,
    replaceSubmitting,
    canReplace,
    replaceId,
    itemsApi,
    gemsApi,
    selectSlot,
    selectGemSlot,
    selectReplaceGem,
    addGem,
    replaceGem,
  } = useSeerAttachGemFlow({ characterId, onSuccess });

  const handleSelectItem = (option: DropdownItem): void => {
    selectSlot(Number(option.value));
  };

  const handleSelectGem = (option: DropdownItem): void => {
    selectGemSlot(Number(option.value));
  };

  const hasAttachedGemsToReplace =
    comparison !== null && comparison.attached_gems.length > 0;

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

  const renderComparisonLoading = (): ReactNode => {
    if (!comparisonLoading) {
      return null;
    }

    return <p role="status">Loading comparison…</p>;
  };

  const renderForm = (): ReactNode => (
    <div className="space-y-4">
      <label id="seer-attach-item-label" className="block font-semibold">
        Item
      </label>
      <Dropdown
        aria_labelled_by="seer-attach-item-label"
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
        empty_message="No items are available."
        disabled={itemsApi.loading}
      />

      <label id="seer-attach-gem-label" className="block font-semibold">
        Gem
      </label>
      <Dropdown
        aria_labelled_by="seer-attach-gem-label"
        items={gemsApi.items}
        on_select={handleSelectGem}
        selection_placeholder={
          gemsApi.loading ? 'Loading Gems…' : 'Select a Gem'
        }
        searchable
        search_value={gemsApi.searchText}
        on_search={gemsApi.setSearchText}
        can_load_more={gemsApi.canLoadMore}
        is_loading_more={gemsApi.isLoadingMore}
        on_end_reached={gemsApi.onEndReached}
        empty_message="No Gems are available."
        disabled={gemsApi.loading}
      />

      {hasAttachedGemsToReplace && comparison && (
        <SeerReplaceGemForm
          attachedGems={comparison.attached_gems}
          selectedGemId={replaceId}
          onSelect={selectReplaceGem}
        />
      )}
    </div>
  );

  const renderPreview = (): ReactNode => {
    if (comparisonLoading) {
      return renderComparisonLoading();
    }

    if (!comparison) {
      return null;
    }

    return (
      <CraftingActionPreview title="Gem attachment preview">
        <SeerGemComparison comparison={comparison} />
        <p>Attach Gem cost: {costs.attach} Gold Bars.</p>
        {hasAttachedGemsToReplace && (
          <p>Replace Gem cost: {costs.replace} Gold Bars.</p>
        )}
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="flex flex-col gap-2 sm:flex-row">
      {comparison && (
        <Button
          label="Add Gem"
          on_click={() => void addGem()}
          variant={ButtonVariant.PRIMARY}
          disabled={addSubmitting}
          additional_css="w-full sm:w-auto"
        />
      )}
      {hasAttachedGemsToReplace && (
        <Button
          label="Replace Gem"
          on_click={() => void replaceGem()}
          variant={ButtonVariant.PRIMARY}
          disabled={!canReplace || replaceSubmitting}
          additional_css="w-full sm:w-auto"
        />
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
          Seer Camp: Attach or Replace Gem
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

export default SeerAttachGemForm;
