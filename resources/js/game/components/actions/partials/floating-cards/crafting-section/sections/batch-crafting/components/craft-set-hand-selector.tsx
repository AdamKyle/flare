import React, { ReactNode, useEffect, useRef, useState } from 'react';

import CraftSetHandSelectorProps from './types/craft-set-hand-selector-props';
import { useCraftableItemsApi } from '../../crafting/api/hooks/use-craftable-items-api';
import {
  armourTypeOptions,
  weaponCraftTypeOptions,
} from '../../crafting/utils/crafting-options';
import { useCraftSetHandRecommendation } from '../api/hooks/use-craft-set-hand-recommendation';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const shieldOption = armourTypeOptions.find(
  (option) => option.value === 'shield'
);

const HAND_TYPE_OPTIONS: DropdownItem[] = shieldOption
  ? [...weaponCraftTypeOptions, shieldOption]
  : [...weaponCraftTypeOptions];

const CraftSetHandSelector = ({
  label,
  selected_item,
  on_select,
}: CraftSetHandSelectorProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const [handType, setHandType] = useState<DropdownItem | null>(null);
  const [searchText, setSearchText] = useState('');
  const appliedRecommendationKeyRef = useRef<string | null>(null);

  const isShieldType = handType?.value === 'shield';

  const selectedWeaponType =
    weaponCraftTypeOptions.find((option) => option.value === handType?.value)
      ?.value ?? null;

  const selectedHandType = isShieldType ? 'armour' : selectedWeaponType;

  const recommendationHandType = isShieldType ? 'shield' : selectedWeaponType;

  const {
    recommendation,
    loading: recommendationLoading,
    error: recommendationError,
  } = useCraftSetHandRecommendation({
    characterId,
    handType: recommendationHandType,
  });

  const {
    items,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText: setApiSearchText,
  } = useCraftableItemsApi({
    characterId,
    selectedType: selectedHandType,
    armourType: isShieldType ? 'shield' : null,
    itemType: null,
  });

  const itemOptions: DropdownItem[] = items.map((item) => ({
    label: item.preview.name,
    value: item.id,
  }));

  useEffect(() => {
    if (!recommendation || !recommendationHandType) {
      return;
    }

    const recommendationKey = `${recommendationHandType}:${recommendation.item_id}`;

    if (appliedRecommendationKeyRef.current === recommendationKey) {
      return;
    }

    appliedRecommendationKeyRef.current = recommendationKey;

    on_select({
      label: recommendation.item_name,
      value: recommendation.item_id,
    });
  }, [recommendation, recommendationHandType, on_select]);

  const handleClear = () => {
    setHandType(null);
    appliedRecommendationKeyRef.current = null;
    on_select(null);
  };

  const handleTypeSelect = (item: DropdownItem) => {
    setHandType(item);
    appliedRecommendationKeyRef.current = null;
    on_select(null);
  };

  const handleSearch = (value: string) => {
    setSearchText(value);
    setApiSearchText(value);
  };

  const typeLegendId = `craft-set-hand-type-${label}`
    .toLowerCase()
    .replace(/[^a-z0-9-]/g, '-');
  const itemLegendId = `craft-set-hand-item-${label}`
    .toLowerCase()
    .replace(/[^a-z0-9-]/g, '-');

  const showRecommendationLoading = recommendationLoading && !selected_item;

  const itemEmptyMessage = (() => {
    if (loading || showRecommendationLoading) {
      return 'Loading craftable items...';
    }

    return 'No craftable items found.';
  })();

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-gray-700 dark:text-gray-300">
          {label}
        </p>
        {selected_item && (
          <Button
            label="Clear"
            variant={ButtonVariant.PRIMARY}
            on_click={handleClear}
          />
        )}
      </div>
      <fieldset>
        <legend
          id={typeLegendId}
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          {label} Item Type
        </legend>
        <Dropdown
          aria_labelled_by={typeLegendId}
          items={HAND_TYPE_OPTIONS}
          on_select={handleTypeSelect}
          pre_selected_item={handType ?? undefined}
          selection_placeholder="Select a type"
        />
      </fieldset>
      {selectedHandType && (
        <fieldset>
          <legend
            id={itemLegendId}
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {label} Item
          </legend>
          <Dropdown
            aria_labelled_by={itemLegendId}
            items={itemOptions}
            on_select={on_select}
            pre_selected_item={selected_item ?? undefined}
            selection_placeholder={`Select ${label}`}
            searchable
            search_value={searchText}
            on_search={handleSearch}
            can_load_more={canLoadMore}
            is_loading_more={isLoadingMore}
            on_end_reached={onEndReached}
            empty_message={itemEmptyMessage}
            search_placeholder={`Search ${label}`}
          />
          {recommendationError && (
            <Alert variant={AlertVariant.DANGER}>{recommendationError}</Alert>
          )}
        </fieldset>
      )}
    </div>
  );
};

export default CraftSetHandSelector;
