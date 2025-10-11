import clsx from 'clsx';
import { capitalize } from 'lodash';
import React, { useState } from 'react';

import { ItemBaseTypes } from './enums/item-base-type';
import { ItemPositions } from './enums/item-positions';
import EquipItemActionProps from './types/equip-item-action-props';
import { getItemPositions } from './utils/get-item-position';
import { getType } from './utils/get-type';
import { isTwoHandedType } from './utils/item-comparison';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { formatNumberWithCommas } from '../../util/format-number';

import ActionBoxBase from 'ui/action-boxes/action-box-base';
import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import LinkButton from 'ui/buttons/link-button';

const EquipItemActions = ({
  comparison_details,
  on_confirm_action,
  on_close_equip_action,
  is_processing,
  is_equipping,
}: EquipItemActionProps) => {
  const [equippedPosition, setEquippedPosition] =
    useState<ItemPositions | null>(null);

  const itemToEquip = comparison_details[0].item_to_equip;

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

  const handleConfirmation = (position: ItemPositions) => {
    setEquippedPosition(position);

    const foundComparison = comparison_details.find((detail) => {
      return detail.position === position;
    });

    if (!foundComparison) {
      return;
    }

    on_confirm_action({
      position,
      slot_id: is_equipping
        ? itemToEquip.slot_id || 0
        : foundComparison.equipped_item.slot_id,
      equip_type: itemToEquip.type,
      item_id_to_buy: itemToEquip.item_id,
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
        className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500"
      >
        <i className="fas fa-times" aria-hidden="true"></i>
      </button>
    );
  };

  const renderHeader = () => {
    if (!comparison_details) {
      return null;
    }

    return (
      <div className="mb-2 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <h4 className="text-gray-800 dark:text-gray-300 font-bold">
            Equip Item Options
          </h4>
        </div>

        {renderHeaderClose()}
      </div>
    );
  };

  const renderDualSlotActions = () => {
    if (baseType === ItemBaseTypes.Armour) {
      return null;
    }

    const labels = getDualSlotLabels();

    if (!labels) {
      return null;
    }

    const positions = getItemPositions(itemToEquip);

    if (!positions) {
      return null;
    }

    return (
      <div className="grid grid-cols-2 gap-2 items-stretch">
        <IconButton
          disabled={is_processing}
          on_click={() => handleConfirmation(positions[0] as ItemPositions)}
          label={labels[0]}
          variant={ButtonVariant.SUCCESS}
          additional_css={`w-full justify-center`}
          icon={renderLoadingIcon(positions[0] as ItemPositions)}
        />

        <IconButton
          disabled={is_processing}
          on_click={() => handleConfirmation(positions[1] as ItemPositions)}
          label={labels[1]}
          variant={ButtonVariant.SUCCESS}
          additional_css={`w-full justify-center`}
          icon={renderLoadingIcon(positions[1] as ItemPositions)}
        />
      </div>
    );
  };

  const renderArmourAction = () => {
    if (baseType !== ItemBaseTypes.Armour) {
      return null;
    }

    if (itemToEquip.type === InventoryItemTypes.SHIELD) {
      return null;
    }

    const label = `Replace Equipped: ${capitalize(String(itemToEquip.type).replace('-', ' '))}`;

    const positions = getItemPositions(itemToEquip);

    if (!positions) {
      return null;
    }

    return (
      <div className="flex justify-center">
        <IconButton
          disabled={is_processing}
          on_click={() => handleConfirmation(positions[0] as ItemPositions)}
          label={label}
          variant={ButtonVariant.SUCCESS}
          icon={renderLoadingIcon(positions[0] as ItemPositions)}
        />
      </div>
    );
  };

  const renderCostOfReplacement = () => {
    if (!on_close_equip_action) {
      return null;
    }

    return (
      <div className="mt-4">
        <span className="text-mango-tango-500 dark:text-mango-tango-500">
          <strong>Cost of replacement</strong>
        </span>
        : {formatNumberWithCommas(itemToEquip.cost)}
      </div>
    );
  };

  const renderEquipSummary = () => {
    if (!Array.isArray(comparison_details) || comparison_details.length === 0) {
      return null;
    }

    const isSingleLine = isTwoHanded || baseType === ItemBaseTypes.Armour;

    const itemsToShow = isSingleLine
      ? [comparison_details[0]]
      : comparison_details;

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
            <LinkButton
              label={'here'}
              variant={ButtonVariant.PRIMARY}
              is_external
              on_click={() => {}}
            />
          </p>
        </div>

        <ul className="list-disc pl-5">
          {itemsToShow.map((detail, index) => {
            const equipped = detail.equipped_item;

            const equippedColor = planeTextItemColors(equipped);

            const isEquippedTwoHanded = isTwoHandedType(equipped.type);

            return (
              <li key={`${detail.position}-${index}`}>
                <span className={clsx(equippedColor, 'font-bold')}>
                  {equipped.name}
                </span>
                . Type:{' '}
                <strong>{capitalize(equipped.type.replace('-', ' '))}</strong>{' '}
                {isEquippedTwoHanded ? ' and is two handed ' : ' '} and is
                equipped in:{' '}
                <strong>{capitalize(detail.position.replace('-', ' '))}</strong>
              </li>
            );
          })}
        </ul>

        {renderCostOfReplacement()}
      </div>
    );
  };

  const renderEquipItemDetails = () => {
    if (baseType === ItemBaseTypes.Armour) {
      return renderArmourAction();
    }

    return renderDualSlotActions();
  };

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
