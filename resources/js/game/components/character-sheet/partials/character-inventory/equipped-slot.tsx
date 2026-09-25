import React, { ReactNode } from 'react';

import EquippedSlotProps from './types/equipped-slot-props';
import { fetchEquippedImage } from './utils/fetch-equipped-image';

const EquippedSlot = (props: EquippedSlotProps): ReactNode => {
  const { positionName, position, equipped_item, on_open_item_details } = props;

  const { path, itemName } = fetchEquippedImage(position, equipped_item);

  const slotLabel = `${positionName}: ${itemName}`;

  if (!equipped_item) {
    return (
      <div
        className="flex h-16 w-16 items-center justify-center rounded border border-gray-600 text-white"
        role="img"
        aria-label={slotLabel}
        title={slotLabel}
      >
        <img src={path} width={64} alt="" aria-hidden="true" />
      </div>
    );
  }

  const handleOpenItemDetails = (): void => {
    on_open_item_details(equipped_item);
  };

  return (
    <button
      type="button"
      className={
        'flex h-16 w-16 items-center justify-center rounded border border-gray-600 text-white focus:outline-none ' +
        'focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 ' +
        'hover:bg-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-500'
      }
      onClick={handleOpenItemDetails}
      aria-label={`${slotLabel}. View item details`}
      title={slotLabel}
    >
      <img src={path} width={64} alt="" aria-hidden="true" />
    </button>
  );
};

export default EquippedSlot;
