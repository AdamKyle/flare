import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import { useEnchantingAffixesApi } from '../../enchanting/api/hooks/use-enchanting-affixes-api';
import { useCraftAndEnchantSetPreview } from '../api/hooks/use-craft-and-enchant-set-preview';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import CraftAndEnchantSetEnchantmentRow from '../components/craft-and-enchant-set-enchantment-row';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftSetPosition } from '../enums/craft-set-position';
import { CraftAndEnchantSetEnchantmentsScreenProps } from '../types/batch-crafting-screen-map';
import { craftSetPositionLabel } from '../utils/batch-crafting-labels';
import { buildCraftAndEnchantSetRequest } from '../utils/build-craft-and-enchant-set-request';

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

type EnchantmentSelection = Partial<
  Record<
    CraftSetPosition,
    { prefix: DropdownItem | null; suffix: DropdownItem | null }
  >
>;

const CraftAndEnchantSetEnchantmentsScreen = ({
  set_selection,
}: CraftAndEnchantSetEnchantmentsScreenProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const includedPositions = Object.keys(
    set_selection.selected_items
  ) as CraftSetPosition[];

  const [enchantments, setEnchantments] = useState<EnchantmentSelection>({});
  const [bulkPrefix, setBulkPrefix] = useState<DropdownItem | null>(null);
  const [bulkSuffix, setBulkSuffix] = useState<DropdownItem | null>(null);

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
  } = useCraftAndEnchantSetPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const selectedPositions = useMemo(() => {
    const positions: Partial<Record<CraftSetPosition, DropdownItem | null>> =
      {};

    includedPositions.forEach((position) => {
      const item = set_selection.selected_items[position];

      if (!item) {
        return;
      }

      positions[position] = { label: item.item_name, value: item.item_id };
    });

    return positions;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [set_selection.selected_items]);

  const request = useMemo(
    () =>
      buildCraftAndEnchantSetRequest({
        selectedPositions,
        enchantments,
        outputSelection: set_selection.output_selection,
      }),
    [selectedPositions, enchantments, set_selection.output_selection]
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

  const handleApplyToAll = () => {
    const next: EnchantmentSelection = {};

    includedPositions.forEach((position) => {
      next[position] = { prefix: bulkPrefix, suffix: bulkSuffix };
    });

    setEnchantments(next);
  };

  const handleRowPrefixSelect = (
    position: CraftSetPosition,
    item: DropdownItem
  ) => {
    setEnchantments((current) => ({
      ...current,
      [position]: { prefix: item, suffix: current[position]?.suffix ?? null },
    }));
  };

  const handleRowSuffixSelect = (
    position: CraftSetPosition,
    item: DropdownItem
  ) => {
    setEnchantments((current) => ({
      ...current,
      [position]: { prefix: current[position]?.prefix ?? null, suffix: item },
    }));
  };

  const handleBack = () => {
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

  const canStart =
    request !== null &&
    preview !== null &&
    !previewLoading &&
    preview.blockers.length === 0 &&
    !starting;

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
        <dt className="text-gray-600 dark:text-gray-400">Positions</dt>
        <dd>{preview.included_position_count}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Crafting Gold</dt>
        <dd>{preview.crafting_gold_total.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Enchanting Gold</dt>
        <dd>{preview.enchanting_gold_total.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Total Cost</dt>
        <dd>{preview.total_gold_cost.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Available Gold</dt>
        <dd>{preview.gold_available.toLocaleString()}</dd>
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
      <h3 className="text-lg font-semibold">Enchant Set</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Choose at least one enchantment for every item in the set.
      </p>

      <div className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
          Apply to all
        </p>

        <fieldset>
          <legend
            id="craft-and-enchant-set-bulk-prefix-legend"
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Prefix
          </legend>
          <Dropdown
            aria_labelled_by="craft-and-enchant-set-bulk-prefix-legend"
            items={prefixAffixes}
            on_select={setBulkPrefix}
            pre_selected_item={bulkPrefix ?? undefined}
            selection_placeholder="Select a Prefix"
            force_clear={bulkPrefix === null}
          />
        </fieldset>

        <fieldset>
          <legend
            id="craft-and-enchant-set-bulk-suffix-legend"
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Suffix
          </legend>
          <Dropdown
            aria_labelled_by="craft-and-enchant-set-bulk-suffix-legend"
            items={suffixAffixes}
            on_select={setBulkSuffix}
            pre_selected_item={bulkSuffix ?? undefined}
            selection_placeholder="Select a Suffix"
            force_clear={bulkSuffix === null}
          />
        </fieldset>

        <Button
          label="Apply to all"
          variant={ButtonVariant.PRIMARY}
          on_click={handleApplyToAll}
        />
      </div>

      <div className="space-y-3">
        {includedPositions.map((position) => {
          const item = set_selection.selected_items[position];

          if (!item) {
            return null;
          }

          return (
            <CraftAndEnchantSetEnchantmentRow
              key={position}
              position_label={craftSetPositionLabel(position) ?? position}
              item_name={item.item_name}
              prefix_items={prefixAffixes}
              suffix_items={suffixAffixes}
              selected_prefix={enchantments[position]?.prefix ?? null}
              selected_suffix={enchantments[position]?.suffix ?? null}
              on_prefix_select={(dropdownItem) =>
                handleRowPrefixSelect(position, dropdownItem)
              }
              on_suffix_select={(dropdownItem) =>
                handleRowSuffixSelect(position, dropdownItem)
              }
            />
          );
        })}
      </div>

      {renderPreview()}

      {startError && <Alert variant={AlertVariant.DANGER}>{startError}</Alert>}

      <div className="flex gap-3">
        <Button
          label="Back"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full"
          on_click={handleBack}
        />
        <Button
          label="Batch Craft"
          variant={ButtonVariant.SUCCESS}
          additional_css="w-full"
          disabled={!canStart}
          on_click={handleStart}
        />
      </div>
    </div>
  );
};

export default CraftAndEnchantSetEnchantmentsScreen;
