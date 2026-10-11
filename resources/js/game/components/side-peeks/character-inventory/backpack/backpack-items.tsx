import ApiErrorAlert from 'api-handler/components/api-error-alert';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce, isNil } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';
import { match } from 'ts-pattern';

import BackPackSelectionActions from './back-pack-selection-actions';
import BackpackFilterDefinition, {
  BackpackRarityFilter,
} from './types/backpack-filter-definition';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { GuideTargets } from '../../../guide-quests/walkthrough/registry/guide-targets';
import GenericItemList from '../../components/items/generic-item-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import { useEquipmentManagementRestriction } from '../hooks/use-equipment-management-restriction';
import { useEquipItem } from '../inventory-item/api/hooks/use-equip-item';
import InventoryItemDetailStack from '../inventory-item/inventory-item-detail-stack';
import { SelectedEquippableItemsOptions } from './enums/selected-equippable-items-options';
import BackpackItemsProps from './types/backpack-items-props';
import {
  armourPositions,
  InventoryItemTypes,
  weaponTypes,
} from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { useManageMultipleSelectedItemsApi } from '../hooks/use-manage-multiple-selected-items-api';
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
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const equipmentOptions: DropdownItem[] = [
  { label: 'Weapon', value: 'weapon' },
  { label: 'Armour', value: 'armour' },
  { label: 'Spells', value: 'spells' },
  { label: 'Trinkets', value: 'trinkets' },
  { label: 'Artifacts', value: 'artifacts' },
  { label: 'Rings', value: 'rings' },
];

const weaponItemTypes = weaponTypes.filter(
  (itemType) => itemType !== InventoryItemTypes.SHIELD
);

const weaponSubtypeOptions: DropdownItem[] = weaponItemTypes.map(
  (itemType) => ({
    label: itemType
      .split('-')
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' '),
    value: itemType,
  })
);

const armourItemTypes = [...armourPositions, InventoryItemTypes.SHIELD];

const armourSubtypeOptions: DropdownItem[] = [
  { label: 'Body', value: InventoryItemTypes.BODY },
  { label: 'Helmet', value: InventoryItemTypes.HELMET },
  { label: 'Sleeves', value: InventoryItemTypes.SLEEVES },
  { label: 'Gloves', value: InventoryItemTypes.GLOVES },
  { label: 'Leggings', value: InventoryItemTypes.LEGGINGS },
  { label: 'Feet', value: InventoryItemTypes.FEET },
  { label: 'Shield', value: InventoryItemTypes.SHIELD },
];

const rarityOptions: DropdownItem[] = [
  { label: 'Unique Items', value: 'unique' },
  { label: 'Mythic', value: 'mythic' },
  { label: 'Cosmic', value: 'cosmic' },
  { label: 'Single Enchanted', value: 'single-enchanted' },
  { label: 'Dual Enchanted', value: 'dual-enchanted' },
];

const resolveItemType = (
  value: DropdownItem['value']
): InventoryItemTypes | null =>
  Object.values(InventoryItemTypes).find((itemType) => itemType === value) ??
  null;

const resolveRarity = (
  value: DropdownItem['value']
): BackpackRarityFilter | null => {
  if (value === 'unique') {
    return 'unique';
  }

  if (value === 'mythic') {
    return 'mythic';
  }

  if (value === 'cosmic') {
    return 'cosmic';
  }

  if (value === 'single-enchanted') {
    return 'single-enchanted';
  }

  if (value === 'dual-enchanted') {
    return 'dual-enchanted';
  }

  return null;
};

const resolveEquipmentItemTypes = (
  equipmentFilter: string | null,
  equipmentSubtype: string | null
): InventoryItemTypes[] | undefined => {
  if (equipmentFilter === 'weapon') {
    const subtype = resolveItemType(equipmentSubtype ?? '');

    return subtype === null ? weaponItemTypes : [subtype];
  }

  if (equipmentFilter === 'armour') {
    const subtype = resolveItemType(equipmentSubtype ?? '');

    return subtype === null ? armourItemTypes : [subtype];
  }

  if (equipmentFilter === 'spells') {
    return [InventoryItemTypes.SPELL_DAMAGE, InventoryItemTypes.SPELL_HEALING];
  }

  if (equipmentFilter === 'trinkets') {
    return [InventoryItemTypes.TRINKET];
  }

  if (equipmentFilter === 'artifacts') {
    return [InventoryItemTypes.ARTIFACT];
  }

  if (equipmentFilter === 'rings') {
    return [InventoryItemTypes.RING];
  }

  return undefined;
};

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
  const [equipmentFilter, setEquipmentFilter] = useState<string | null>(null);
  const [equipmentSubtype, setEquipmentSubtype] = useState<string | null>(null);
  const [rarityFilter, setRarityFilter] = useState<BackpackRarityFilter | null>(
    initial_filters?.rarity ?? null
  );
  const [useInitialItemTypes, setUseInitialItemTypes] = useState(
    initial_filters?.item_types !== undefined
  );

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

  useEffect(() => {
    const itemTypes = useInitialItemTypes
      ? initial_filters?.item_types
      : resolveEquipmentItemTypes(equipmentFilter, equipmentSubtype);
    const filters: BackpackFilterDefinition = {};

    if (itemTypes !== undefined && itemTypes.length > 0) {
      filters.item_types = itemTypes;
    }

    if (rarityFilter !== null) {
      filters.rarity = rarityFilter;
    }

    setFilters(filters);
  }, [
    equipmentFilter,
    equipmentSubtype,
    initial_filters?.item_types,
    rarityFilter,
    setFilters,
    useInitialItemTypes,
  ]);

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

  const onEquipSuccess = (successMessage: string) => {
    resetDirectEquipError();
    setEquippedItemSuccessMessage(successMessage);
    setSlotId(null);
    setRefresh((prevState) => !prevState);
    on_equipment_changed?.();
  };

  const {
    is_restricted: isEquipmentRestricted,
    restriction_message: equipmentRestrictionMessage,
  } = useEquipmentManagementRestriction();

  const {
    loading: directEquipLoading,
    error: directEquipError,
    resetError: resetDirectEquipError,
    setRequestParams: setDirectEquipRequestParams,
  } = useEquipItem({
    character_id: character.id,
    on_success: onEquipSuccess,
  });

  const clearItemFeedback = (): void => {
    resetDirectEquipError();
    setEquippedItemSuccessMessage(null);
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

  const handleEquipmentFilterSelected = (item: DropdownItem): void => {
    setUseInitialItemTypes(false);
    setEquipmentFilter(String(item.value));
    setEquipmentSubtype(null);
  };

  const handleEquipmentFilterCleared = (): void => {
    setUseInitialItemTypes(false);
    setEquipmentFilter(null);
    setEquipmentSubtype(null);
  };

  const handleEquipmentSubtypeSelected = (item: DropdownItem): void => {
    const itemType = resolveItemType(item.value);

    if (itemType !== null) {
      setEquipmentSubtype(itemType);
    }
  };

  const handleRaritySelected = (item: DropdownItem): void => {
    setRarityFilter(resolveRarity(item.value));
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
              loading: directEquipLoading,
              disabled: isEquipmentRestricted,
              aria_label: `Equip ${selectedItem.name}`,
              on_click: () => {
                setDirectEquipRequestParams({
                  slot_id: selectedItem.slot_id,
                  equip_type: selectedItem.type,
                  position: target_position,
                });
              },
            },
          ]
        : undefined;
    const notice =
      target_position === undefined ? undefined : (
        <>
          {equipmentRestrictionMessage !== null && (
            <Alert variant={AlertVariant.WARNING}>
              {equipmentRestrictionMessage}
            </Alert>
          )}
          {directEquipError !== null && (
            <ApiErrorAlert apiError={directEquipError.message} />
          )}
        </>
      );

    return (
      <InventoryItemDetailStack
        slot_id={slotId}
        character_id={character.id}
        aria_label="Item Details"
        on_close={handleCloseItemView}
        on_action={onEquipSuccess}
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
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      {renderSuccessMessage()}
      <div className="px-4 pt-2">
        <Input
          on_change={onSearch}
          place_holder={'Search items'}
          clearable
          default_value={initial_search_text ?? null}
        />
      </div>
      <div className="grid gap-2 px-4 pt-2 sm:grid-cols-2">
        <Dropdown
          items={equipmentOptions}
          on_select={handleEquipmentFilterSelected}
          on_clear={handleEquipmentFilterCleared}
          selection_placeholder="Equipment"
        />
        {equipmentFilter === 'weapon' && (
          <Dropdown
            items={weaponSubtypeOptions}
            on_select={handleEquipmentSubtypeSelected}
            on_clear={() => setEquipmentSubtype(null)}
            selection_placeholder="Weapon Type"
          />
        )}
        {equipmentFilter === 'armour' && (
          <Dropdown
            items={armourSubtypeOptions}
            on_select={handleEquipmentSubtypeSelected}
            on_clear={() => setEquipmentSubtype(null)}
            selection_placeholder="Armour Type"
          />
        )}
        <Dropdown
          items={rarityOptions}
          on_select={handleRaritySelected}
          on_clear={() => setRarityFilter(null)}
          selection_placeholder="Rarity / Enchantment"
        />
      </div>
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
