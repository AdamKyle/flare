import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import { useAlchemyItemsApi } from '../../alchemy/api/hooks/use-alchemy-items-api';
import { useAlchemyAmountPreview } from '../api/hooks/use-alchemy-amount-preview';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { dispositionLabel } from '../utils/batch-crafting-labels';
import { buildAlchemyAmountRequest } from '../utils/build-alchemy-amount-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const PREVIEW_DEBOUNCE_MS = 300;

const DISPOSITION_OPTIONS: DropdownItem[] = [
  BatchCraftingDisposition.KEEP,
  BatchCraftingDisposition.DESTROY,
  BatchCraftingDisposition.LIST,
  BatchCraftingDisposition.USE_NOW,
].map((disposition) => ({
  label: dispositionLabel(disposition),
  value: disposition,
}));

const AlchemyAmountScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [disposition, setDisposition] = useState<DropdownItem | null>(null);
  const [selectedItem, setSelectedItem] = useState<DropdownItem | null>(null);
  const [amountText, setAmountText] = useState('1');
  const [listingPriceText, setListingPriceText] = useState('');

  const {
    items: itemOptions,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    searchText,
    setSearchText,
  } = useAlchemyItemsApi({ character_id: characterId });

  const {
    preview,
    loading: previewLoading,
    error: previewError,
    fetchPreview,
    clearPreview,
  } = useAlchemyAmountPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const selectedDisposition =
    typeof disposition?.value === 'string'
      ? (disposition.value as BatchCraftingDisposition)
      : null;

  const request = useMemo(
    () =>
      buildAlchemyAmountRequest({
        selectedItem,
        amountText,
        disposition: selectedDisposition,
        listingPriceText,
      }),
    [selectedItem, amountText, selectedDisposition, listingPriceText]
  );

  const debouncedFetchRef = useRef<ReturnType<typeof debounce> | null>(null);

  useEffect(() => {
    debouncedFetchRef.current?.cancel();
    clearPreview();

    if (!request) {
      return;
    }

    const debouncedFetch = debounce(() => {
      void fetchPreview(request);
    }, PREVIEW_DEBOUNCE_MS);

    debouncedFetchRef.current = debouncedFetch;
    debouncedFetch();

    return () => {
      debouncedFetch.cancel();
    };
  }, [request, fetchPreview, clearPreview]);

  const canStart =
    request !== null &&
    preview !== null &&
    !previewLoading &&
    preview.blockers.length === 0 &&
    !starting;

  const handleStart = async () => {
    if (!request) {
      return;
    }

    const started = await start(request);

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  const renderPreview = () => {
    if (previewLoading) {
      return (
        <IndeterminateProgressBar
          label="Updating preview"
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    if (previewError) {
      return <Alert variant={AlertVariant.DANGER}>{previewError}</Alert>;
    }

    if (!preview) {
      return null;
    }

    return (
      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
        <dt className="text-gray-600 dark:text-gray-400">
          Gold Dust Cost Each
        </dt>
        <dd>{preview.gold_dust_cost_each.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Shards Cost Each</dt>
        <dd>{preview.shards_cost_each.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">
          Total Gold Dust Cost
        </dt>
        <dd>{preview.total_gold_dust_cost.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Total Shards Cost</dt>
        <dd>{preview.total_shards_cost.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">
          Gold Dust Available
        </dt>
        <dd>{preview.gold_dust_available.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Shards Available</dt>
        <dd>{preview.shards_available.toLocaleString()}</dd>
        {preview.alchemy_bag_capacity && (
          <>
            <dt className="text-gray-600 dark:text-gray-400">
              Alchemy Bag Capacity
            </dt>
            <dd>
              {preview.alchemy_bag_capacity.current} /{' '}
              {preview.alchemy_bag_capacity.max}
            </dd>
          </>
        )}
        {preview.blockers.map((blocker) => (
          <dd key={blocker} className="col-span-2">
            <Alert variant={AlertVariant.DANGER}>{blocker}</Alert>
          </dd>
        ))}
      </dl>
    );
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Alchemy Amount</h3>

      <fieldset>
        <legend
          id="alchemy-amount-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="alchemy-amount-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={setDisposition}
          pre_selected_item={disposition ?? undefined}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {selectedDisposition === BatchCraftingDisposition.LIST && (
        <div>
          <label
            htmlFor="alchemy-amount-listing-price"
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Listing Price
          </label>
          <input
            id="alchemy-amount-listing-price"
            type="number"
            min={1}
            value={listingPriceText}
            onChange={(event) => setListingPriceText(event.target.value)}
            className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
      )}

      <fieldset>
        <legend
          id="alchemy-amount-item-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Alchemy Item
        </legend>
        <Dropdown
          aria_labelled_by="alchemy-amount-item-legend"
          items={itemOptions}
          on_select={setSelectedItem}
          pre_selected_item={selectedItem ?? undefined}
          selection_placeholder="Select an Alchemy item"
          searchable
          search_value={searchText}
          on_search={setSearchText}
          can_load_more={canLoadMore}
          is_loading_more={isLoadingMore}
          on_end_reached={onEndReached}
          empty_message={
            loading ? 'Loading Alchemy items...' : 'No Alchemy items found.'
          }
          search_placeholder="Search Alchemy items"
        />
      </fieldset>

      <div>
        <label
          htmlFor="alchemy-amount"
          className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Amount
        </label>
        <input
          id="alchemy-amount"
          type="number"
          min={1}
          max={2000}
          step={1}
          value={amountText}
          onChange={(event) => setAmountText(event.target.value)}
          className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      {renderPreview()}

      {startError && <Alert variant={AlertVariant.DANGER}>{startError}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canStart}
        on_click={handleStart}
      />

      <Button
        label="Back"
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={() => navigation.pop()}
      />
    </div>
  );
};

export default AlchemyAmountScreen;
