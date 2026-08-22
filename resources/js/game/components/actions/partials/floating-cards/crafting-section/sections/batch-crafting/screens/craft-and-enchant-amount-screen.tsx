import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import { useCraftableItemsApi } from '../../crafting/api/hooks/use-craftable-items-api';
import {
  armourTypeOptions,
  craftTypeOptions,
} from '../../crafting/utils/crafting-options';
import { useEnchantingAffixesApi } from '../../enchanting/api/hooks/use-enchanting-affixes-api';
import { useCraftAndEnchantAmountPreview } from '../api/hooks/use-craft-and-enchant-amount-preview';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftAndEnchantAmountScreenProps } from '../types/batch-crafting-screen-map';
import {
  dispositionLabel,
  outputDestinationLabel,
} from '../utils/batch-crafting-labels';
import { buildCraftAndEnchantAmountRequest } from '../utils/build-craft-and-enchant-amount-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const PREVIEW_DEBOUNCE_MS = 300;

const CRAFT_TYPE_OPTIONS: DropdownItem[] = craftTypeOptions.filter(
  (option) => option.value !== 'for-class'
);

const SECTION_HEADING_CSS =
  'text-sm font-semibold text-gray-900 dark:text-gray-100';

const CraftAndEnchantAmountScreen = ({
  output_selection,
}: CraftAndEnchantAmountScreenProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [craftingType, setCraftingType] = useState<DropdownItem | null>(null);
  const [armourType, setArmourType] = useState<DropdownItem | null>(null);
  const [selectedItem, setSelectedItem] = useState<DropdownItem | null>(null);
  const [prefix, setPrefix] = useState<DropdownItem | null>(null);
  const [suffix, setSuffix] = useState<DropdownItem | null>(null);
  const [amountText, setAmountText] = useState('1');
  const [searchText, setSearchTextState] = useState('');

  const selectedCraftingType =
    craftTypeOptions.find((option) => option.value === craftingType?.value)
      ?.value ?? null;
  const selectedArmourType =
    armourTypeOptions.find((option) => option.value === armourType?.value)
      ?.value ?? null;

  const {
    items,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  } = useCraftableItemsApi({
    characterId,
    selectedType: selectedCraftingType,
    armourType: selectedArmourType,
    itemType: null,
  });

  const { affixes: prefixAffixes } = useEnchantingAffixesApi({
    character_id: characterId,
    type: 'prefix',
  });

  const { affixes: suffixAffixes } = useEnchantingAffixesApi({
    character_id: characterId,
    type: 'suffix',
  });

  const {
    preview,
    loading: previewLoading,
    error: previewError,
    fetchPreview,
    clearPreview,
  } = useCraftAndEnchantAmountPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const request = useMemo(
    () =>
      buildCraftAndEnchantAmountRequest({
        craftingType,
        selectedItem,
        prefix,
        suffix,
        amountText,
        outputSelection: output_selection,
      }),
    [craftingType, selectedItem, prefix, suffix, amountText, output_selection]
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

  const handleSearch = (value: string) => {
    setSearchTextState(value);
    setSearchText(value);
  };

  const handleCraftingTypeSelect = (item: DropdownItem) => {
    setCraftingType(item);
    setArmourType(null);
    setSelectedItem(null);
    setSearchTextState('');
    setSearchText('');
  };

  const handleArmourTypeSelect = (item: DropdownItem) => {
    setArmourType(item);
    setSelectedItem(null);
    setSearchTextState('');
    setSearchText('');
  };

  const handleItemSelect = (item: DropdownItem) => {
    setSelectedItem(item);
  };

  const handlePrefixSelect = (item: DropdownItem) => {
    setPrefix(item);
  };

  const handleSuffixSelect = (item: DropdownItem) => {
    setSuffix(item);
  };

  const handleAmountChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setAmountText(event.target.value);
  };

  const handleChangeOutput = () => {
    navigation.pop();
  };

  const handleStart = async () => {
    if (!request) {
      return;
    }

    const started = await start(request);

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  const itemOptions: DropdownItem[] = items.map((item) => ({
    label: item.preview.name,
    value: item.id,
  }));

  const canStart =
    request !== null &&
    preview !== null &&
    !previewLoading &&
    preview.blockers.length === 0 &&
    !starting;

  const renderArmourTypeField = () => {
    if (craftingType?.value !== 'armour') {
      return null;
    }

    return (
      <fieldset>
        <legend
          id="craft-and-enchant-amount-armour-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Armour Type
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-amount-armour-type-legend"
          items={armourTypeOptions}
          on_select={handleArmourTypeSelect}
          pre_selected_item={armourType ?? undefined}
          selection_placeholder="Select an armour type"
          force_clear={armourType === null}
        />
      </fieldset>
    );
  };

  const renderOutputSummary = () => {
    const destinationSummaryValue = outputDestinationLabel(
      output_selection.output_destination
    );

    return (
      <div className="space-y-2">
        <h4 className={SECTION_HEADING_CSS}>Output</h4>
        <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Action</dt>
          <dd>{dispositionLabel(output_selection.disposition)}</dd>
          {output_selection.disposition === BatchCraftingDisposition.KEEP && (
            <>
              <dt className="text-gray-600 dark:text-gray-400">Destination</dt>
              <dd>{destinationSummaryValue}</dd>
            </>
          )}
          {output_selection.disposition === BatchCraftingDisposition.LIST && (
            <>
              <dt className="text-gray-600 dark:text-gray-400">
                Listing Price
              </dt>
              <dd>{output_selection.listing_price?.toLocaleString()}</dd>
            </>
          )}
        </dl>
        <LinkButton
          label="Change"
          variant={ButtonVariant.PRIMARY}
          on_click={handleChangeOutput}
        />
      </div>
    );
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
        <dt className="text-gray-600 dark:text-gray-400">Crafting Cost</dt>
        <dd>{preview.crafting_gold_cost_each.toLocaleString()} Gold</dd>
        <dt className="text-gray-600 dark:text-gray-400">Enchanting Cost</dt>
        <dd>{preview.enchanting_gold_cost_each.toLocaleString()} Gold</dd>
        <dt className="text-gray-600 dark:text-gray-400">Total Cost Each</dt>
        <dd>{preview.total_gold_cost_each.toLocaleString()} Gold</dd>
        <dt className="text-gray-600 dark:text-gray-400">
          Total Requested Cost
        </dt>
        <dd>{preview.total_requested_gold_cost.toLocaleString()} Gold</dd>
        <dt className="text-gray-600 dark:text-gray-400">Available Gold</dt>
        <dd>{preview.gold_available.toLocaleString()} Gold</dd>
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
      <h3 className="text-lg font-semibold">Craft and Enchant Amount</h3>

      {renderOutputSummary()}

      <fieldset>
        <legend
          id="craft-and-enchant-amount-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Crafting Type
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-amount-type-legend"
          items={CRAFT_TYPE_OPTIONS}
          on_select={handleCraftingTypeSelect}
          pre_selected_item={craftingType ?? undefined}
          selection_placeholder="Select a craft type"
        />
      </fieldset>

      {renderArmourTypeField()}

      <fieldset>
        <legend
          id="craft-and-enchant-amount-item-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Item
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-amount-item-legend"
          items={itemOptions}
          on_select={handleItemSelect}
          pre_selected_item={selectedItem ?? undefined}
          selection_placeholder="Select a craftable item"
          disabled={
            !craftingType || (craftingType.value === 'armour' && !armourType)
          }
          searchable
          search_value={searchText}
          on_search={handleSearch}
          can_load_more={canLoadMore}
          is_loading_more={isLoadingMore}
          on_end_reached={onEndReached}
          empty_message={
            loading ? 'Loading craftable items...' : 'No craftable items found.'
          }
          search_placeholder="Search craftable items"
        />
      </fieldset>

      <fieldset>
        <legend
          id="craft-and-enchant-amount-prefix-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Prefix
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-amount-prefix-legend"
          items={prefixAffixes}
          on_select={handlePrefixSelect}
          pre_selected_item={prefix ?? undefined}
          selection_placeholder="Select a Prefix"
          force_clear={prefix === null}
        />
      </fieldset>

      <fieldset>
        <legend
          id="craft-and-enchant-amount-suffix-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Suffix
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-amount-suffix-legend"
          items={suffixAffixes}
          on_select={handleSuffixSelect}
          pre_selected_item={suffix ?? undefined}
          selection_placeholder="Select a Suffix"
          force_clear={suffix === null}
        />
      </fieldset>

      <div>
        <label
          htmlFor="craft-and-enchant-amount"
          className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Amount
        </label>
        <input
          id="craft-and-enchant-amount"
          type="number"
          min={1}
          max={2000}
          step={1}
          value={amountText}
          onChange={handleAmountChange}
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
    </div>
  );
};

export default CraftAndEnchantAmountScreen;
