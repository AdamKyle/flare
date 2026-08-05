import React, { ReactNode } from 'react';

import QueenCostSummary from './queen-cost-summary';
import QueenRerollFormProps from './types/queen-reroll-form-props';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import { useQueenRerollFlow } from '../hooks/use-queen-reroll-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';

const QueenRerollForm = ({
  data,
  characterId,
  rootStatus,
  helpLink,
  onSuccess,
  onDataReplaced,
  onChangeAction,
}: QueenRerollFormProps): ReactNode => {
  const {
    hasSlots,
    itemsApi,
    affixOptions,
    rerollTypeOptions,
    selectedSlotId,
    selectedSlot,
    selectedAffix,
    selectedRerollType,
    selectedCost,
    resultPreview,
    submitting,
    error,
    canSubmit,
    handleSelectSlot,
    handleSelectAffix,
    handleSelectRerollType,
    handleSubmit,
  } = useQueenRerollFlow({ characterId, data, onDataReplaced, onSuccess });

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

  const renderForm = (): ReactNode => {
    if (!hasSlots) {
      return (
        <Alert variant={AlertVariant.INFO}>
          You do not have any unique items to re-roll.
        </Alert>
      );
    }

    return (
      <div className="space-y-4">
        <label id="queen-reroll-item-label" className="block font-semibold">
          Unique item
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-item-label"
          items={itemsApi.items}
          selection_placeholder={
            itemsApi.loading ? 'Loading items…' : 'Select a unique item'
          }
          on_select={handleSelectSlot}
          searchable
          search_value={itemsApi.searchText}
          on_search={itemsApi.setSearchText}
          can_load_more={itemsApi.canLoadMore}
          is_loading_more={itemsApi.isLoadingMore}
          on_end_reached={itemsApi.onEndReached}
          empty_message="No unique items are available."
          disabled={itemsApi.loading}
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
      </div>
    );
  };

  const renderPreview = (): ReactNode => {
    if (!selectedSlot) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Reroll preview"
        description="The replacement is random. The affix category selected below will be rerolled to a new, randomly generated value."
      >
        <CraftingItemPreview item={selectedSlot.preview} />
        {selectedAffix && (
          <p className="text-sm text-gray-700 dark:text-gray-300">
            Reroll target: {selectedAffix}
            {selectedRerollType ? ` (${selectedRerollType})` : ''}
          </p>
        )}
        {selectedCost && (
          <QueenCostSummary
            goldDust={selectedCost.gold_dust}
            shards={selectedCost.shards}
          />
        )}
      </CraftingActionPreview>
    );
  };

  const renderResult = (): ReactNode => {
    if (!resultPreview) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>The item was re-rolled.</span>
        <div className="mt-2">
          <CraftingItemPreview item={resultPreview} />
        </div>
      </Alert>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="flex flex-col gap-2 sm:flex-row">
      {hasSlots && (
        <Button
          label={submitting ? 'Re rolling…' : 'Re roll'}
          on_click={() => void handleSubmit()}
          variant={ButtonVariant.PRIMARY}
          disabled={!canSubmit}
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
          Queen of Hearts: Reroll an Affix
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

export default QueenRerollForm;
