import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce, isNil } from 'lodash';
import React, { useMemo, useState } from 'react';
import { match } from 'ts-pattern';

import BackPackSelectionActions from './back-pack-selection-actions';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import GenericItemList from '../../components/items/generic-item-list';
import GenericItemProps from '../../components/items/types/generic-item-props';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import InventoryItem from '../inventory-item/inventory-item';
import { SelectedEquippableItemsOptions } from './enums/selected-equippable-items-options';
import { useManageMultipleSelectedItemsApi } from '../hooks/use-manage-multiple-selected-items-api';
import { ItemSelectedType } from '../types/item-selected-type';

import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-count-definition';
import { GameDataError } from 'game-data/components/game-data-error';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const BackpackItems = ({
  character,
  on_switch_view,
  update_character,
}: GenericItemProps) => {
  const [slotId, setSlotId] = useState<number | null>(null);
  const [selection, setSelection] = useState<ItemSelectedType | null>(null);
  const [actionSelected, setActionSelected] =
    useState<SelectedEquippableItemsOptions | null>(null);
  const [isSelectionDisabled, setIsSelectionDisabled] = useState(false);
  const [closeSuccessMessage, setCloseSuccessMessage] = useState(false);

  const { data, error, loading, setSearchText, onEndReached, setRefresh } =
    UsePaginatedApiHandler<EquippableItemWithBase>({
      url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
      urlParams: { character: character.id },
    });

  const {
    successMessage,
    error: multipleSelectionError,
    handleSelection,
    loading: multipleSelectionLoading,
  } = useManageMultipleSelectedItemsApi();

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    []
  );

  const handleOnItemClick = (slot_id: number) => {
    setSlotId(slot_id);
  };

  const handleSelectionChange = (update: ItemSelectedType) => {
    setSelection(update);
  };

  const handleMultiActionApiSuccess = (
    inventory_count: InventoryCountDefinition
  ) => {
    setActionSelected(null);
    setIsSelectionDisabled(false);
    setCloseSuccessMessage(false);

    if (update_character) {
      update_character({ inventory_count: inventory_count });
    }

    setRefresh((prev) => !prev);
  };

  const handleActionSectionSubmission = () => {
    if (!selection) {
      return;
    }

    const url = match(actionSelected)
      .with(
        SelectedEquippableItemsOptions.SELL,
        () => CharacterInventoryApiUrls.CHARACTER_SELL_SELECTED
      )
      .with(
        SelectedEquippableItemsOptions.DESSTROY,
        () => CharacterInventoryApiUrls.CHARACTER_DESTROY_SELECTED
      )
      .with(
        SelectedEquippableItemsOptions.DISENCHANT,
        () => CharacterInventoryApiUrls.CHARACTER_DISENCHANT_SELECTED
      )
      .otherwise(() => null);

    if (!url) {
      return;
    }

    setCloseSuccessMessage(true);

    handleSelection({
      character_id: character.id,
      onSuccess: handleMultiActionApiSuccess,
      apiParams: selection,
      url: url,
    });
  };

  const onActionBarClose = () => {
    setActionSelected(null);
    setIsSelectionDisabled(false);
    setCloseSuccessMessage(true);
  };

  const onSearch = (value: string) => {
    debouncedSetSearchText(value.trim());
    setCloseSuccessMessage(true);
  };

  const handleCloseItemView = () => {
    setSlotId(null);
    setCloseSuccessMessage(true);
  };

  const onMultiActionSelected = (actionSelected: DropdownItem) => {
    setActionSelected(actionSelected.value as SelectedEquippableItemsOptions);
    setIsSelectionDisabled(true);
    setCloseSuccessMessage(true);
  };

  const { handleScroll: handleInventoryScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  if (error || multipleSelectionError) {
    return (
      <div className={'p-4'}>
        <GameDataError />
      </div>
    );
  }

  if (loading) {
    return (
      <div className={'p-4'}>
        <InfiniteLoader />
      </div>
    );
  }

  const renderMultipleActionSuccess = () => {
    if (!successMessage) {
      return;
    }

    return (
      <div className="my-2">
        <Alert
          variant={AlertVariant.SUCCESS}
          closable
          force_close={closeSuccessMessage}
        >
          {successMessage}
        </Alert>
      </div>
    );
  };

  const renderActionBarForSelection = () => {
    if (isNil(actionSelected)) {
      return null;
    }

    return (
      <BackPackSelectionActions
        action_type={actionSelected}
        on_action_bar_close={onActionBarClose}
        on_submit_action={handleActionSectionSubmission}
        is_loading={multipleSelectionLoading}
      />
    );
  };

  const renderSelectionActions = () => {
    if (selection === null) {
      return null;
    }

    if (selection.mode === 'include') {
      if (!selection.ids || selection.ids.length === 0) {
        return null;
      }
    }

    const options = [
      {
        label: 'Sell Selected',
        value: SelectedEquippableItemsOptions.SELL,
      },
      {
        label: 'Destroy Selected',
        value: SelectedEquippableItemsOptions.DESSTROY,
      },
      {
        label: 'Disenchant Selected',
        value: SelectedEquippableItemsOptions.DISENCHANT,
      },
    ];

    return (
      <div className="pt-2 px-4">
        <Dropdown
          items={options}
          on_select={onMultiActionSelected}
          selection_placeholder={'Select an action'}
          disabled={isSelectionDisabled}
        />
        {renderActionBarForSelection()}
      </div>
    );
  };

  const renderInventoryOverlay = () => {
    if (isNil(slotId)) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseItemView}>
        <InventoryItem slot_id={slotId} character_id={character.id} />
      </StackedCard>
    );
  };

  return (
    <div className="relative flex flex-col h-full overflow-hidden">
      <div className="flex justify-center p-4">
        <Button
          on_click={() => on_switch_view(false)}
          label="Quest Items"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      {renderMultipleActionSuccess()}
      <div className="pt-2 px-4">
        <Input on_change={onSearch} place_holder={'Search items'} clearable />
      </div>
      {renderSelectionActions()}
      <div className="flex-1 min-h-0">
        <GenericItemList
          items={data}
          is_quest_items={false}
          on_scroll_to_end={handleInventoryScroll}
          on_click={handleOnItemClick}
          on_selection_change={handleSelectionChange}
          is_selection_disabled={isSelectionDisabled}
        />
      </div>
      <AnimatePresence mode="wait">{renderInventoryOverlay()}</AnimatePresence>
    </div>
  );
};

export default BackpackItems;
