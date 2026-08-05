import React, { ReactNode } from 'react';

import QueenCostSummary from './queen-cost-summary';
import QueenMoveAffixesFormProps from './types/queen-move-affixes-form-props';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import QueenInventorySlotDefinition from '../api/definitions/queen-inventory-slot-definition';
import { QueenAffixSelection } from '../enums/queen-affix-selection';
import { useQueenMoveAffixesFlow } from '../hooks/use-queen-move-affixes-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';

const describeMovingAffixes = (
  source: QueenInventorySlotDefinition | null,
  affix: QueenAffixSelection | null
): string[] => {
  if (!source || !affix) {
    return [];
  }

  const names: string[] = [];

  if (
    (affix === QueenAffixSelection.PREFIX ||
      affix === QueenAffixSelection.ALL_ENCHANTMENTS) &&
    source.preview.item_prefix
  ) {
    names.push(`Prefix: ${source.preview.item_prefix.name}`);
  }

  if (
    (affix === QueenAffixSelection.SUFFIX ||
      affix === QueenAffixSelection.ALL_ENCHANTMENTS) &&
    source.preview.item_suffix
  ) {
    names.push(`Suffix: ${source.preview.item_suffix.name}`);
  }

  return names;
};

const QueenMoveAffixesForm = ({
  data,
  characterId,
  rootStatus,
  helpLink,
  onSuccess,
  onDataReplaced,
  onChangeAction,
}: QueenMoveAffixesFormProps): ReactNode => {
  const {
    hasSourceSlots,
    sourceItemsApi,
    destinationItemsApi,
    affixOptions,
    selectedSourceId,
    selectedSource,
    selectedDestination,
    selectedAffix,
    selectedCost,
    sourceResultPreview,
    destinationResultPreview,
    submitting,
    error,
    canSubmit,
    handleSelectSource,
    handleSelectAffix,
    handleSelectDestination,
    handleSubmit,
  } = useQueenMoveAffixesFlow({ characterId, data, onDataReplaced, onSuccess });

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
    if (!hasSourceSlots) {
      return (
        <Alert variant={AlertVariant.INFO}>
          You do not have any unique items with affixes to move.
        </Alert>
      );
    }

    return (
      <div className="space-y-4">
        <label id="queen-move-source-label" className="block font-semibold">
          Source item
        </label>
        <Dropdown
          aria_labelled_by="queen-move-source-label"
          items={sourceItemsApi.items}
          selection_placeholder={
            sourceItemsApi.loading ? 'Loading items…' : 'Select the source item'
          }
          on_select={handleSelectSource}
          searchable
          search_value={sourceItemsApi.searchText}
          on_search={sourceItemsApi.setSearchText}
          can_load_more={sourceItemsApi.canLoadMore}
          is_loading_more={sourceItemsApi.isLoadingMore}
          on_end_reached={sourceItemsApi.onEndReached}
          empty_message="No unique items are available."
          disabled={sourceItemsApi.loading}
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
          items={destinationItemsApi.items}
          selection_placeholder={
            destinationItemsApi.loading
              ? 'Loading destination items…'
              : 'Select the destination item'
          }
          on_select={handleSelectDestination}
          searchable
          search_value={destinationItemsApi.searchText}
          on_search={destinationItemsApi.setSearchText}
          can_load_more={destinationItemsApi.canLoadMore}
          is_loading_more={destinationItemsApi.isLoadingMore}
          on_end_reached={destinationItemsApi.onEndReached}
          empty_message="No eligible destination items are available."
          disabled={!selectedSourceId || destinationItemsApi.loading}
        />
      </div>
    );
  };

  const renderPreview = (): ReactNode => {
    if (!selectedSource || !selectedDestination) {
      return null;
    }

    const movingAffixes = describeMovingAffixes(selectedSource, selectedAffix);

    return (
      <CraftingActionPreview title="Move preview">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <p className="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
              Source
            </p>
            <CraftingItemPreview item={selectedSource.preview} />
          </div>
          <div>
            <p className="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
              Destination
            </p>
            <CraftingItemPreview item={selectedDestination.preview} />
          </div>
        </div>
        {movingAffixes.length > 0 && (
          <ul className="list-inside list-disc text-sm text-gray-700 dark:text-gray-300">
            {movingAffixes.map((name) => (
              <li key={name}>{name}</li>
            ))}
          </ul>
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
    if (!sourceResultPreview && !destinationResultPreview) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>The affixes were moved.</span>
        <div className="mt-2 grid grid-cols-1 gap-4 md:grid-cols-2">
          {sourceResultPreview && (
            <div>
              <p className="mb-1 text-xs font-semibold">Source result</p>
              <CraftingItemPreview item={sourceResultPreview} />
            </div>
          )}
          {destinationResultPreview && (
            <div>
              <p className="mb-1 text-xs font-semibold">Destination result</p>
              <CraftingItemPreview item={destinationResultPreview} />
            </div>
          )}
        </div>
      </Alert>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="flex flex-col gap-2 sm:flex-row">
      {hasSourceSlots && (
        <Button
          label={submitting ? 'Moving…' : 'Move Enchants'}
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
          Queen of Hearts: Move Enchants
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

export default QueenMoveAffixesForm;
