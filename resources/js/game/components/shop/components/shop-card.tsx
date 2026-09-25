import React from 'react';

import CurrencyDisplay from '../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../reusable-components/currency/enums/currency-type';
import { UNSUPPORTED_EQUIPMENT_MESSAGE } from '../../../reusable-components/item/constants/unsupported-equipment-message';
import { ItemBaseTypes } from '../../../reusable-components/item/enums/item-base-type';
import { getType } from '../../../reusable-components/item/utils/get-type';
import { armourPositions } from '../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import ShopCardProps from '../types/shop-card-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const ShopCard = ({
  item,
  row_key,
  view_item,
  compare_item,
  view_buy_many,
  on_purchase_item,
  is_actions_disabled,
}: ShopCardProps) => {
  const itemType = getType(item, armourPositions);

  const renderAttackOrDefence = () => {
    if (itemType === null) {
      return (
        <Alert variant={AlertVariant.DANGER}>
          {UNSUPPORTED_EQUIPMENT_MESSAGE}
        </Alert>
      );
    }
    if (itemType === ItemBaseTypes.Armour) {
      return (
        <span>
          <strong>AC:</strong> {`+${item.raw_ac}`}
        </span>
      );
    }

    return (
      <span>
        <strong>Damage:</strong> {`+${item.raw_damage}`}
      </span>
    );
  };

  return (
    <>
      <div className="flex items-start justify-between">
        <h3
          id={`item-${row_key}-name`}
          className="text-danube-600 dark:text-danube-300 flex-1 text-lg font-semibold break-words"
        >
          {item.name}
        </h3>
        <LinkButton
          label="View"
          variant={ButtonVariant.PRIMARY}
          on_click={() => view_item(item)}
        />
      </div>
      <p className="mt-2 text-gray-700 dark:text-gray-300">
        {renderAttackOrDefence()}
      </p>
      <p className="mt-1 flex items-center gap-1 font-medium text-gray-800 dark:text-gray-200">
        <span>Cost:</span>
        <CurrencyDisplay
          currency={CurrencyType.GOLD}
          amount={item.cost}
          display_mode={CurrencyDisplayMode.EXACT}
        />
      </p>
      <div className="mt-4 flex flex-wrap gap-2">
        <Button
          on_click={() => compare_item(item)}
          label="Compare"
          variant={ButtonVariant.SUCCESS}
          disabled={is_actions_disabled || itemType === null}
        />
        <Button
          on_click={() => on_purchase_item(item.item_id)}
          label="Buy"
          variant={ButtonVariant.PRIMARY}
          disabled={is_actions_disabled}
        />
        <Button
          on_click={() => view_buy_many(item)}
          label="Buy Multiple"
          variant={ButtonVariant.PRIMARY}
          disabled={is_actions_disabled}
        />
      </div>
    </>
  );
};

export default ShopCard;
