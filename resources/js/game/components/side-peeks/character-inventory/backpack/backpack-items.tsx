import ApiErrorAlert from 'api-handler/components/api-error-alert';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce, isNil } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';
import { match } from 'ts-pattern';

import BackPackSelectionActions from './back-pack-selection-actions';
import BackpackFilters from './components/backpack-filters';
import { useBackpackFilters } from './hooks/use-backpack-filters';
import { useTargetedBackpackEquip } from './hooks/use-targeted-backpack-equip';
import BackpackFilterDefinition from './types/backpack-filter-definition';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { GuideTargets } from '../../../guide-quests/walkthrough/registry/guide-targets';
import GenericItemList from '../../components/items/generic-item-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import InventoryItemDetailStack from '../inventory-item/inventory-item-detail-stack';
import { SelectedEquippableItemsOptions } from './enums/selected-equippable-items-options';
import BackpackItemsProps from './types/backpack-items-props';
import { useManageMultipleSelectedItemsApi } from '../api/hooks/use-manage-multiple-selected-items-api';
import { ItemSelectedType } from '../types/item-selected-type';

import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';
import { GameDataError } from 'game-data/components/game-data-error';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const resolveSelectedEquippableItemsOption = (
  value: DropdownItem['value']
): SelectedEquippableItemsOptions | null => {
  if (value === SelectedEquippableItemsOptions.SELL) {
    return SelectedEquippableItemsOptions.SELL;
  }

  if (value === SelectedEquippableItemsOptions.DESSTROY) {
    return SelectedEquippableItemsOptions.DESSTROY;
  }

  if (value === SelectedEquippableItemsOptions.DISENCHANT) {
    return SelectedEquippableItemsOptions.DISENCHANT;
  }

  return null;
};

const BackpackItems = ({
  character,
  on_switch_view,
  update_character,
  initial_search_text,
  initial_filters,
  target_position,
  on_equipment_changed,
}: BackpackItemsProps) => {
  const [slotId, setSlotId] = useState<number | null>(null);
  const [selection, setSelection] = useState<ItemSelectedType | null>(null);
  const [actionSelected, setActionSelected] =
    useState<SelectedEquippableItemsOptions | null>(null);
  const [isSelectionDisabled, setIsSelectionDisabled] = useState(false);
  const [closeSuccessMessage, setCloseSuccessMessage] = useState(false);
  const [equippedItemSuccessMessage, setEquippedItemSuccessMessage] = useState<
    string | null
  >(null);

  const {
    data,
    error,
    loading,
    setSearchText,
    onEndReached,
    setRefresh,
    setFilters,
  } = UsePaginatedApiHandler<EquippableItemWithBase, BackpackFilterDefinition>({
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
    urlParams: { character: character.id },
    initialSearchText: initial_search_text,
    initialFilters: initial_filters,
  });

  const backpackFilters = useBackpackFilters({
    initial_filters,
    setFilters,
  });

  const {
    successMessage,
    error: multipleSelectionError,
    handleSelection,
    loading: multipleSelectionLoading,
  } = useManageMultipleSelectedItemsApi();

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    [setSearchText]
  );

  useEffect(() => {
    return () => {
      debouncedSetSearchText.cancel();
    };
  }, [debouncedSetSearchText]);

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

  const targetedEquip = useTargetedBackpackEquip({
    character_id: character.id,
    target_position,
    on_equipment_changed,
    refresh_backpack: () => setRefresh((previous) => !previous),
    close_item_detail: () => setSlotId(null),
    set_success_message: setEquippedItemSuccessMessage,
  });

  const clearItemFeedback = (): void => {
    targetedEquip.resetFeedback();
  };

  const handleItemActionSuccess = (successMessage: string): void => {
    setEquippedItemSuccessMessage(successMessage);
    setSlotId(null);
    setRefresh((previous) => !previous);
    on_equipment_changed?.();
  };

  const handleOnItemClick = (slot_id: number) => {
    clearItemFeedback();
    setSlotId(slot_id);
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
    clearItemFeedback();
    setSlotId(null);
    setCloseSuccessMessage(true);
  };

  const onMultiActionSelected = (actionSelected: DropdownItem) => {
    const resolvedAction = resolveSelectedEquippableItemsOption(
      actionSelected.value
    );

    if (resolvedAction === null) {
      return;
    }

    setActionSelected(resolvedAction);
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

  const renderSuccessMessage = () => {
    if (!successMessage && !equippedItemSuccessMessage) {
      return;
    }

    let message = successMessage;

    if (equippedItemSuccessMessage) {
      message = equippedItemSuccessMessage;
    }

    return (
      <div className="my-2">
        <Alert
          variant={AlertVariant.SUCCESS}
          closable
          force_close={closeSuccessMessage}
        >
          {message}
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
      <div className="px-4 pt-2">
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

    const selectedItem = data.find((item) => item.slot_id === slotId) ?? null;
    const footerOptions: SidePeekOptionDefinition[] | undefined =
      target_position !== undefined && selectedItem !== null
        ? [
            {
              id: 'equip-target-position',
              label: 'Equip',
              loading_label: 'Equipping...',
              variant: ButtonVariant.SUCCESS,
              loading: targetedEquip.loading,
              disabled: targetedEquip.is_restricted,
              aria_label: `Equip ${selectedItem.name}`,
              on_click: () => {
                targetedEquip.equipSelectedItem(selectedItem);
              },
            },
          ]
        : undefined;
    const notice =
      target_position === undefined ? undefined : (
        <>
          {targetedEquip.restriction_message !== null && (
            <Alert variant={AlertVariant.WARNING}>
              {targetedEquip.restriction_message}
            </Alert>
          )}
          {targetedEquip.error !== null && (
            <ApiErrorAlert apiError={targetedEquip.error.message} />
          )}
        </>
      );

    return (
      <InventoryItemDetailStack
        slot_id={slotId}
        character_id={character.id}
        aria_label="Item Details"
        on_close={handleCloseItemView}
        on_action={handleItemActionSuccess}
        show_actions={target_position === undefined}
        footer_options={footerOptions}
        notice={notice}
      />
    );
  };

  return (
    <div
      className="relative flex h-full flex-col overflow-hidden"
      data-guide-target={GuideTargets.INVENTORY_BACKPACK_CONTENT}
    >
      <div className="flex justify-center p-4">
        <Button
          on_click={() => on_switch_view(false)}
          label="Quest Items"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <Separator />
      {renderSuccessMessage()}
      <div className="px-4 pt-2">
        <Input
          on_change={onSearch}
          place_holder={'Search items'}
          clearable
          default_value={initial_search_text ?? null}
        />
      </div>
      <BackpackFilters {...backpackFilters} />
      {renderSelectionActions()}
      <div className="min-h-0 flex-1">
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
