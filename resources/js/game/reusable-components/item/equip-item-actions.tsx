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
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const EquipItemActions = ({
  comparisonDetails,
  on_buy_and_replace,
  on_close_buy_and_equip,
}: EquipItemActionProps) => {
  const [isLoading, setIsLoading] = useState(false);

  const [equippedPosition, setEquippedPosition] =
    useState<ItemPositions | null>(null);

  const itemToEquip = comparisonDetails[0].item_to_equip;

  const baseType = getType(itemToEquip, armourPositions);

  const isTwoHanded = isTwoHandedType(itemToEquip.type);

  const nameColorClass = planeTextItemColors(itemToEquip);

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

  const handleBuyAndReplace = (position: ItemPositions) => {
    setEquippedPosition(position);

    const foundComparison = comparisonDetails.find((detail) => {
      return detail.position === position;
    });

    if (!foundComparison) {
      return;
    }

    on_buy_and_replace(
      position,
      foundComparison.equipped_item.slot_id,
      foundComparison.equipped_item.type,
      itemToEquip.item_id
    );
  };

  const handleTimeIconClick = () => {};

  const handleCloseBuyAndReplace = () => {
    setEquippedPosition(null);

    on_close_buy_and_equip();
  };

  const renderHeader = () => {
    if (!comparisonDetails) {
      return null;
    }

    return (
      <div className="mb-2 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <h4 className="text-gray-800 dark:text-gray-300 font-bold">
            Equip Item Details
          </h4>
        </div>

        <button
          type="button"
          onClick={handleCloseBuyAndReplace}
          aria-label="Close"
          title="Close"
          className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500"
        >
          <i className="fas fa-times" aria-hidden="true"></i>
        </button>
      </div>
    );
  };

  const renderTwoHandedAction = () => {
    if (
      baseType !== ItemBaseTypes.Weapon &&
      itemToEquip.type !== InventoryItemTypes.SHIELD
    ) {
      return null;
    }

    if (!isTwoHanded) {
      return null;
    }

    const label = `Equip as a two handed: ${capitalize(String(itemToEquip.type).replace('-', ' '))}`;

    return (
      <div className="flex justify-center">
        <Button
          disabled={isLoading}
          on_click={() => handleBuyAndReplace(ItemPositions.LEFT_HAND)}
          label={label}
          variant={ButtonVariant.SUCCESS}
          additional_css={nameColorClass}
        />
      </div>
    );
  };

  const renderDualSlotActions = () => {
    if (baseType === ItemBaseTypes.Armour) {
      return null;
    }

    if (isTwoHanded) {
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
        <Button
          disabled={isLoading}
          on_click={() => handleBuyAndReplace(positions[0] as ItemPositions)}
          label={labels[0]}
          variant={ButtonVariant.SUCCESS}
          additional_css={`w-full justify-center ${nameColorClass}`}
        />

        <Button
          disabled={isLoading}
          on_click={() => handleBuyAndReplace(positions[1] as ItemPositions)}
          label={labels[1]}
          variant={ButtonVariant.SUCCESS}
          additional_css={`w-full justify-center ${nameColorClass}`}
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
        <Button
          disabled={isLoading}
          on_click={() => handleBuyAndReplace(positions[0] as ItemPositions)}
          label={label}
          variant={ButtonVariant.SUCCESS}
          additional_css={nameColorClass}
        />
      </div>
    );
  };

  const renderEquipSummary = () => {
    if (!Array.isArray(comparisonDetails) || comparisonDetails.length === 0) {
      return null;
    }

    const isSingleLine = isTwoHanded || baseType === ItemBaseTypes.Armour;

    const itemsToShow = isSingleLine
      ? [comparisonDetails[0]]
      : comparisonDetails;

    return (
      <div className="text-gray-800 dark:text-gray-300">
        <div className="my-4">
          <p>
            Select one of the items listed below to replace this item with. The
            equipped item you choose will be placed in your backpack.
          </p>

          <p className="my-2">
            If the item equipped is two handed you can pick any hand you want.
            If the item to equip is two handed and you have two items equipped,
            both will be placed in your inventory in favour of this item.
          </p>
        </div>

        <ul className="list-disc pl-5">
          {itemsToShow.map((detail, index) => {
            const equipped = detail.equipped_item;

            const equippedColor = planeTextItemColors(equipped);

            const isEquippedTwoHanded = isTwoHandedType(equipped.type);

            return (
              <li key={`${detail.position}-${index}`}>
                <span className={equippedColor}>{equipped.name}</span> Type:{' '}
                <strong>{capitalize(equipped.type.replace('-', ' '))}</strong>{' '}
                {isEquippedTwoHanded ? ' and is two handed ' : ' '} and is
                equipped in:{' '}
                <strong>{capitalize(detail.position.replace('-', ' '))}</strong>
              </li>
            );
          })}
        </ul>

        <div className="mt-4">
          <span className="text-mango-tango-500 dark:text-mango-tango-500">
            <strong>Cost of replacement</strong>
          </span>
          : {formatNumberWithCommas(itemToEquip.cost)}
        </div>
      </div>
    );
  };

  const renderEquipItemDetails = () => {
    if (isTwoHanded) {
      return renderTwoHandedAction();
    }

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
