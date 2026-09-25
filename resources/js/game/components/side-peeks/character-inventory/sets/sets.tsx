import ApiErrorAlert from 'api-handler/components/api-error-alert';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, {
  ReactNode,
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react';

import { useEmptySetApi } from './api/hooks/use-empty-set-api';
import { useEquipSetApi } from './api/hooks/use-equip-set-api';
import { useRemoveItemFromSetApi } from './api/hooks/use-remove-item-from-set-api';
import { useUnequipSetApi } from './api/hooks/use-unequip-set-api';
import SetStatusNotices from './components/set-status-notices';
import SetOptionDefinition from './definitions/set-options-definition';
import SetChoices from './set-choices';
import SetsProps from './types/sets-props';
import { buildSetFooterOptions } from './utils/build-set-footer-options';
import { resolveSetActionAvailability } from './utils/resolve-set-action-availability';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import GenericItemList from '../../components/items/generic-item-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import { useEquipmentManagementRestriction } from '../hooks/use-equipment-management-restriction';
import InventoryItemDetailStack from '../inventory-item/inventory-item-detail-stack';

import { GameDataError } from 'game-data/components/game-data-error';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const Sets = ({
  character_id,
  initial_search_text,
  initial_set_id,
  initial_set_name,
  on_equipment_changed,
}: SetsProps): ReactNode => {
  const { is_restricted, restriction_message } =
    useEquipmentManagementRestriction();

  const [slotId, setSlotId] = useState<number | null>(null);
  const [selectedSet, setSelectedSet] = useState<SetOptionDefinition | null>(
    null
  );
  const [selectedSetId, setSelectedSetId] = useState<number | null>(
    initial_set_id ?? null
  );
  const [setChoicesKey, setSetChoicesKey] = useState(0);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const {
    data,
    error,
    loading,
    onEndReached,
    setSearchText,
    setFilters,
    setPage,
    setRefresh,
  } = UsePaginatedApiHandler<EquippableItemWithBase>({
    url: CharacterInventoryApiUrls.CHARACTER_SET_ITEMS,
    urlParams: { character: character_id },
    initialSearchText: initial_search_text,
    initialFilters: initial_set_id ? { set_id: initial_set_id } : undefined,
    enabled: selectedSetId !== null,
  });

  const { handleScroll: handleSetScrolling } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    [setSearchText]
  );

  useEffect(() => {
    return () => {
      debouncedSetSearchText.cancel();
    };
  }, [debouncedSetSearchText]);

  const selectSet = useCallback(
    (set: SetOptionDefinition): void => {
      setSelectedSet(set);
      setSelectedSetId(set.set_id);
      setFilters({ set_id: set.set_id });
    },
    [setFilters]
  );

  const refreshSetData = useCallback((): void => {
    setSetChoicesKey((previousKey) => previousKey + 1);
    setPage(1);
    setRefresh((previousValue) => !previousValue);
  }, [setPage, setRefresh]);

  const handleSetActionSuccess = useCallback(
    (message: string): void => {
      setSuccessMessage(message);
      refreshSetData();
    },
    [refreshSetData]
  );

  const handleEquipmentActionSuccess = useCallback(
    (message: string): void => {
      handleSetActionSuccess(message);

      if (on_equipment_changed) {
        on_equipment_changed();
      }
    },
    [handleSetActionSuccess, on_equipment_changed]
  );

  const handleRemoveItemSuccess = useCallback(
    (message: string): void => {
      setSlotId(null);
      handleSetActionSuccess(message);
    },
    [handleSetActionSuccess]
  );

  const equipSetApi = useEquipSetApi({
    character_id,
    on_success: handleEquipmentActionSuccess,
  });

  const unequipSetApi = useUnequipSetApi({
    character_id,
    on_success: handleEquipmentActionSuccess,
  });

  const emptySetApi = useEmptySetApi({
    character_id,
    on_success: handleSetActionSuccess,
  });

  const removeItemFromSetApi = useRemoveItemFromSetApi({
    character_id,
    on_success: handleRemoveItemSuccess,
  });

  const availability = useMemo(
    () => resolveSetActionAvailability(selectedSet),
    [selectedSet]
  );

  const { equipSet, loading: isEquipping } = equipSetApi;
  const { unequipSet, loading: isUnequipping } = unequipSetApi;
  const { emptySet, loading: isEmptying } = emptySetApi;

  const footerOptions = useMemo((): SidePeekOptionDefinition[] => {
    if (selectedSet === null || slotId !== null) {
      return [];
    }

    const setId = selectedSet.set_id;

    return buildSetFooterOptions({
      availability,
      is_equipment_restricted: is_restricted,
      is_equipping: isEquipping,
      is_unequipping: isUnequipping,
      is_emptying: isEmptying,
      on_equip: () => void equipSet(setId),
      on_unequip: () => void unequipSet(),
      on_empty: () => void emptySet(setId),
    });
  }, [
    selectedSet,
    slotId,
    availability,
    is_restricted,
    isEquipping,
    isUnequipping,
    isEmptying,
    equipSet,
    unequipSet,
    emptySet,
  ]);

  useSidePeekOptions(footerOptions);

  const onSearch = (value: string) => {
    debouncedSetSearchText(value.trim());
  };

  const handleSetChange = (set: SetOptionDefinition) => {
    setSuccessMessage(null);
    selectSet(set);
  };

  const handleClearSetSelection = () => {
    setSuccessMessage(null);
    setSelectedSet(null);
    setSelectedSetId(null);
    setFilters({});
  };

  const handleOnItemClick = (slot_id: number) => {
    setSlotId(slot_id);
  };

  const closeItemView = () => {
    setSlotId(null);
  };

  const handleItemAction = (message: string) => {
    handleSetActionSuccess(message);
  };

  const resolveItemFooterOptions = (
    itemSlotId: number
  ): SidePeekOptionDefinition[] => {
    if (selectedSet === null || selectedSet.equipped) {
      return [];
    }

    const inventorySetId = selectedSet.set_id;

    return [
      {
        id: 'remove-from-set',
        label: 'Remove From Set',
        loading_label: 'Removing...',
        variant: ButtonVariant.DANGER,
        loading: removeItemFromSetApi.loading,
        on_click: () =>
          void removeItemFromSetApi.removeItemFromSet({
            inventory_set_id: inventorySetId,
            slot_id: itemSlotId,
          }),
      },
    ];
  };

  const renderActionError = (): ReactNode => {
    const actionError =
      equipSetApi.error ?? unequipSetApi.error ?? emptySetApi.error;

    if (actionError === null) {
      return null;
    }

    return <ApiErrorAlert apiError={actionError.message} />;
  };

  const renderSuccessMessage = (): ReactNode => {
    if (successMessage === null) {
      return null;
    }

    return (
      <Alert
        variant={AlertVariant.SUCCESS}
        closable
        on_close={() => setSuccessMessage(null)}
      >
        {successMessage}
      </Alert>
    );
  };

  const renderSetStatus = (): ReactNode => {
    if (selectedSet === null) {
      return null;
    }

    return (
      <SetStatusNotices
        selected_set={selectedSet}
        restriction_message={restriction_message}
        show_restriction={availability.can_equip || availability.can_unequip}
        is_violating_set_rules={availability.is_violating_set_rules}
      />
    );
  };

  const renderSetItems = (): ReactNode => {
    if (error) {
      return (
        <div className="p-4">
          <GameDataError />
        </div>
      );
    }

    if (selectedSetId === null) {
      return (
        <p className="p-4 text-center text-sm text-gray-700 dark:text-gray-300">
          Select a set to view its items.
        </p>
      );
    }

    if (loading && data.length === 0) {
      return (
        <div className="p-4">
          <InfiniteLoader />
        </div>
      );
    }

    return (
      <GenericItemList
        items={data}
        is_quest_items={false}
        is_selectable={false}
        on_scroll_to_end={handleSetScrolling}
        on_click={handleOnItemClick}
        empty_message="This set has no items."
      />
    );
  };

  const renderItemDetailNotice = (): ReactNode => {
    if (removeItemFromSetApi.error === null) {
      return null;
    }

    return <ApiErrorAlert apiError={removeItemFromSetApi.error.message} />;
  };

  const renderInventoryItemView = (): ReactNode => {
    if (slotId === null) {
      return null;
    }

    return (
      <InventoryItemDetailStack
        slot_id={slotId}
        character_id={character_id}
        aria_label="Set Item Details"
        on_close={closeItemView}
        on_action={handleItemAction}
        show_actions={false}
        footer_options={resolveItemFooterOptions(slotId)}
        notice={renderItemDetailNotice()}
      />
    );
  };

  return (
    <div className="relative flex h-full flex-col overflow-hidden">
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="px-4 pt-2">
        <Input
          on_change={onSearch}
          place_holder={'Search items'}
          clearable
          default_value={initial_search_text ?? null}
        />
      </div>
      <div className="px-4 pt-2">
        <SetChoices
          key={setChoicesKey}
          character_id={character_id}
          on_set_change={handleSetChange}
          on_set_selection_clear={handleClearSetSelection}
          on_preselected_set_resolved={selectSet}
          set_equipped_set_name
          initial_set_id={selectedSet?.set_id ?? initial_set_id}
          initial_set_name={selectedSet?.name ?? initial_set_name}
        />
      </div>
      <div className="flex flex-col gap-2 px-4 pt-2">
        {renderSetStatus()}
        {renderActionError()}
        {renderSuccessMessage()}
      </div>
      <div className="min-h-0 flex-1">{renderSetItems()}</div>

      <AnimatePresence initial={false} mode="wait">
        {renderInventoryItemView()}
      </AnimatePresence>
    </div>
  );
};

export default Sets;
