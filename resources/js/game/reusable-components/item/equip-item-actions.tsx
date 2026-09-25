import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import { UNSUPPORTED_EQUIPMENT_MESSAGE } from './constants/unsupported-equipment-message';
import { ItemBaseTypes } from './enums/item-base-type';
import { ItemPositions } from './enums/item-positions';
import EquipItemActionProps from './types/equip-item-action-props';
import { getItemPositions } from './utils/get-item-position';
import { getType } from './utils/get-type';
import { isTwoHandedType } from './utils/item-comparison';
import {
  resolveItemPositionLabel,
  resolveItemTypeLabel,
} from './utils/resolve-item-labels';
import { ItemComparisonRow } from '../../api-definitions/items/item-comparison-details';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';
import CurrencyDisplay from '../currency/currency-display';
import { CurrencyDisplayMode } from '../currency/enums/currency-display-mode';
import { CurrencyType } from '../currency/enums/currency-type';

import ActionBoxBase from 'ui/action-boxes/action-box-base';
import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';
import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const EquipItemActions = ({
  comparison_details,
  on_confirm_action,
  on_close_equip_action,
  is_processing,
}: EquipItemActionProps) => {
  const [equippedPosition, setEquippedPosition] =
    useState<ItemPositions | null>(null);

  const itemToEquip = comparison_details.item_to_equip;
  const comparisonRows = comparison_details.details;

  const baseType = getType(itemToEquip, armourPositions);

  const isTwoHanded = isTwoHandedType(itemToEquip.type);

  const getDualSlotLabels = () => {
    if (baseType === ItemBaseTypes.Ring) {
      return ['Ring One', 'Ring Two'];
    }

    if (baseType === ItemBaseTypes.Spell) {
      return ['Spell One', 'Spell Two'];
    }

    if (
      baseType === ItemBaseTypes.Weapon ||
      itemToEquip.type === InventoryItemTypes.SHIELD
    ) {
      return ['Left Hand', 'Right Hand'];
    }

    return null;
  };

  const resolveReplacementSlotId = (position: ItemPositions): number | null => {
    const foundComparison = comparisonRows.find(
      (detail) => detail.position === position
    );

    if (foundComparison) {
      return foundComparison.equipped_item.slot_id;
    }

    return itemToEquip.slot_id;
  };

  const handleConfirmation = (position: ItemPositions) => {
    const slotId = resolveReplacementSlotId(position);

    if (slotId === null) {
      return;
    }

    setEquippedPosition(position);

    on_confirm_action({
      position,
      slot_id: slotId,
      equip_type: itemToEquip.type,
      item_id: itemToEquip.item_id,
    });
  };

  const handleCloseBuyAndReplace = () => {
    setEquippedPosition(null);

    if (!on_close_equip_action) {
      return;
    }

    on_close_equip_action();
  };

  const renderLoadingIcon = (position: ItemPositions) => {
    if (!is_processing) {
      return null;
    }

    if (equippedPosition !== position) {
      return null;
    }

    return <i className="fas fa-spinner fa-spin" aria-hidden="true"></i>;
  };

  const renderHeaderClose = () => {
    if (!on_close_equip_action) {
      return null;
    }

    return (
      <button
        type="button"
        onClick={handleCloseBuyAndReplace}
        aria-label="Close"
        title="Close"
        className="rounded p-1 hover:bg-gray-100 focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 focus:outline-none dark:hover:bg-gray-800"
      >
        <i className="fas fa-times" aria-hidden="true"></i>
      </button>
    );
  };

  const renderHeader = () => (
    <div className="mb-2 flex items-center justify-between">
      <div className="flex items-center gap-2">
        <h4 className="font-bold text-gray-800 dark:text-gray-300">
          Equip Item Options
        </h4>
      </div>

      {renderHeaderClose()}
    </div>
  );

  const renderDualSlotActions = () => {
    if (
      baseType === ItemBaseTypes.Armour ||
      baseType === ItemBaseTypes.Trinket ||
      baseType === ItemBaseTypes.Artifact ||
      baseType === null
    ) {
      return null;
    }

    const labels = getDualSlotLabels();

    if (!labels) {
      return null;
    }

    const positions = getItemPositions(itemToEquip);

    if (positions?.length !== 2) {
      return null;
    }

    const [primarySlotPosition, secondarySlotPosition] = positions;

    return (
      <div className="grid grid-cols-2 items-stretch gap-2">
        <IconButton
          disabled={is_processing}
          on_click={() => handleConfirmation(primarySlotPosition)}
          label={labels[0]}
          variant={ButtonVariant.SUCCESS}
          additional_css="w-full justify-center"
          icon={renderLoadingIcon(primarySlotPosition)}
        />

        <IconButton
          disabled={is_processing}
          on_click={() => handleConfirmation(secondarySlotPosition)}
          label={labels[1]}
          variant={ButtonVariant.SUCCESS}
          additional_css="w-full justify-center"
          icon={renderLoadingIcon(secondarySlotPosition)}
        />
      </div>
    );
  };

  const renderSingleSlotAction = () => {
    if (
      baseType !== ItemBaseTypes.Armour &&
      baseType !== ItemBaseTypes.Trinket &&
      baseType !== ItemBaseTypes.Artifact
    ) {
      return null;
    }

    if (itemToEquip.type === InventoryItemTypes.SHIELD) {
      return null;
    }

    const label = `Replace Equipped: ${resolveItemTypeLabel(itemToEquip.type)}`;

    const positions = getItemPositions(itemToEquip);

    if (positions?.length !== 1) {
      return null;
    }

    const [position] = positions;

    return (
      <div className="flex justify-center">
        <IconButton
          disabled={is_processing}
          on_click={() => handleConfirmation(position)}
          label={label}
          variant={ButtonVariant.SUCCESS}
          icon={renderLoadingIcon(position)}
        />
      </div>
    );
  };

  const renderCostOfReplacement = () => {
    if (!on_close_equip_action) {
      return null;
    }

    return (
      <div className="mt-4 flex flex-wrap items-center gap-1">
        <strong className="text-mango-tango-600 dark:text-mango-tango-400">
          Cost of replacement:
        </strong>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={itemToEquip.cost}
          display_mode={CurrencyDisplayMode.EXACT}
        />
      </div>
    );
  };

  const renderTwoHandedNote = (type: InventoryItemTypes): ReactNode => {
    if (!isTwoHandedType(type)) {
      return null;
    }

    return ' and is two handed';
  };

  const renderEquippedItem = (detail: ItemComparisonRow): ReactNode => (
    <li key={`${detail.position}-${detail.equipped_item.slot_id}`}>
      <span
        className={clsx(planeTextItemColors(detail.equipped_item), 'font-bold')}
      >
        {detail.equipped_item.name}
      </span>
      . Type: <strong>{resolveItemTypeLabel(detail.equipped_item.type)}</strong>
      {renderTwoHandedNote(detail.equipped_item.type)} and is equipped in:{' '}
      <strong>{resolveItemPositionLabel(detail.position)}</strong>
    </li>
  );

  const renderEquipSummary = () => {
    if (comparisonRows.length === 0) {
      return null;
    }

    const isSingleLine = isTwoHanded || baseType === ItemBaseTypes.Armour;

    const itemsToShow = isSingleLine ? [comparisonRows[0]] : comparisonRows;

    return (
      <div className="text-gray-800 dark:text-gray-300">
        <div className="my-4">
          <p>
            Select one of the items listed below to replace this item with. The
            equipped item you choose will be placed in your backpack.
          </p>

          <p className="my-2">
            If the item is a weapon, regardless of two handed or not, picking
            the right hand to equip it in can become vital if you plan to use
            Attack and Cast or Cast and Attack. Attack and Cast will use the
            weapon in your left hand while Cast and Attack will use the weapon
            in your right hand. You can learn more{' '}
            <a
              href="/information/combat"
              target="_blank"
              rel="noopener noreferrer"
              className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
            >
              about combat
              <span className="sr-only"> (opens in a new tab)</span>
            </a>
            .
          </p>
        </div>

        <ul className="list-disc pl-5">
          {itemsToShow.map(renderEquippedItem)}
        </ul>

        {renderCostOfReplacement()}
      </div>
    );
  };

  const renderEquipItemDetails = () => {
    if (baseType === ItemBaseTypes.Armour) {
      return renderSingleSlotAction();
    }

    if (
      baseType === ItemBaseTypes.Trinket ||
      baseType === ItemBaseTypes.Artifact
    ) {
      return renderSingleSlotAction();
    }

    return renderDualSlotActions();
  };

  if (baseType === null) {
    return (
      <Alert variant={AlertVariant.DANGER}>
        {UNSUPPORTED_EQUIPMENT_MESSAGE}
      </Alert>
    );
  }

  return (
    <ActionBoxBase
      variant={ActionBoxVariant.DEFAULT}
      actions={renderEquipItemDetails()}
    >
      {renderHeader()}

      <div>{renderEquipSummary()}</div>
    </ActionBoxBase>
  );
};

export default EquipItemActions;
