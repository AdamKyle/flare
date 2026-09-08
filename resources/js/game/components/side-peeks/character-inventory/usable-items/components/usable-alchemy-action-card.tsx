import React, { ReactNode, useState } from 'react';

import UsableAlchemyActionCardProps from './types/usable-alchemy-action-card-props';
import UsableItem from '../../../components/items/usable-item';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import NumberField from 'ui/forms/number-field';

const UsableAlchemyActionCard = ({
  item,
  legal_use_count: legalUseCount,
  using_slot_id: usingSlotId,
  on_click: onClick,
  on_use_one: onUseOne,
  on_use_quantity: onUseQuantity,
  on_use_all: onUseAll,
}: UsableAlchemyActionCardProps): ReactNode => {
  const [showQuantity, setShowQuantity] = useState(false);
  const [quantity, setQuantity] = useState(1);

  const isSpecialItem = item.holy_level !== null || item.damages_kingdoms;
  const canShowActions = item.slot_id !== null && item.usable && !isSpecialItem;
  const isBusy = usingSlotId !== null && usingSlotId === item.slot_id;

  const handleQuantityChange = (value: string): void => {
    const parsed = parseInt(value, 10);

    if (Number.isNaN(parsed)) {
      setQuantity(1);

      return;
    }

    setQuantity(Math.min(Math.max(parsed, 1), Math.max(legalUseCount, 1)));
  };

  const handleOpenQuantity = (): void => {
    setQuantity(1);
    setShowQuantity(true);
  };

  const handleCancelQuantity = (): void => {
    setShowQuantity(false);
    setQuantity(1);
  };

  const handleUseQuantity = (): void => {
    if (item.slot_id === null) {
      return;
    }

    onUseQuantity(item.slot_id, quantity);
    setShowQuantity(false);
  };

  const renderQuantityControl = (): ReactNode => {
    if (item.slot_id === null) {
      return null;
    }

    const quantityFieldId = `use-quantity-${item.slot_id}`;

    return (
      <div className="flex flex-col gap-2">
        <NumberField
          id={quantityFieldId}
          label="Use quantity"
          value={String(quantity)}
          on_change={handleQuantityChange}
          min={1}
          max={legalUseCount}
          disabled={isBusy}
        />
        <div className="flex flex-wrap items-center gap-2">
          <LoadingButton
            label={`Use ${quantity}`}
            loading_label={`Using ${quantity}...`}
            variant={ButtonVariant.ALCHEMY}
            is_loading={isBusy}
            on_click={handleUseQuantity}
          />
          <Button
            label="Cancel"
            variant={ButtonVariant.DANGER}
            disabled={isBusy}
            on_click={handleCancelQuantity}
          />
        </div>
      </div>
    );
  };

  const renderActionButtons = (): ReactNode => {
    if (item.slot_id === null || legalUseCount <= 0) {
      return null;
    }

    if (!item.can_stack) {
      return (
        <LoadingButton
          label="Use Item"
          loading_label="Using Item..."
          variant={ButtonVariant.ALCHEMY}
          is_loading={isBusy}
          on_click={() => item.slot_id !== null && onUseOne(item.slot_id)}
        />
      );
    }

    if (item.amount === 1) {
      if (legalUseCount !== 1) {
        return null;
      }

      return (
        <LoadingButton
          label="Use Item"
          loading_label="Using Item..."
          variant={ButtonVariant.ALCHEMY}
          is_loading={isBusy}
          on_click={() => item.slot_id !== null && onUseOne(item.slot_id)}
        />
      );
    }

    const showUseAll = legalUseCount === item.amount;

    return (
      <>
        <Button
          label="Use X"
          variant={ButtonVariant.ALCHEMY}
          disabled={isBusy}
          aria_busy={isBusy}
          on_click={handleOpenQuantity}
        />
        {showUseAll && (
          <LoadingButton
            label="Use All"
            loading_label="Using All..."
            variant={ButtonVariant.ALCHEMY}
            is_loading={isBusy}
            on_click={() => item.slot_id !== null && onUseAll(item.slot_id)}
          />
        )}
      </>
    );
  };

  return (
    <div className="mb-3 flex flex-col gap-2">
      <div className="flex items-center justify-end">
        <span className="bg-glacier-100 text-glacier-800 dark:bg-glacier-900 dark:text-glacier-200 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
          Stack: {item.amount}
        </span>
      </div>

      <UsableItem item={item} on_click={onClick} />

      {canShowActions && (
        <div className="flex flex-wrap items-center gap-2">
          {showQuantity ? renderQuantityControl() : renderActionButtons()}
        </div>
      )}
    </div>
  );
};

export default UsableAlchemyActionCard;
