import React, { ReactNode } from 'react';

import {
  defaultPositionImage,
  defaultPositionImageAlt,
} from './enums/equipment-positions';
import EquippedSlotProps from './types/equipped-slot-props';

const EquippedSlot = (props: EquippedSlotProps): ReactNode => {
  const { positionName, position } = props;

  let path = defaultPositionImage[position];
  let itemName = 'Nothing Equipped';
  const altText = defaultPositionImageAlt[position];

  return (
    <button
      className={
        'w-16 h-16 text-white flex items-center justify-center border border-gray-600 rounded focus:outline-none ' +
        'focus:ring-2 focus:ring-offset-2 focus:ring-gray-600 ' +
        'hover:bg-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-500'
      }
      onClick={() => {}}
      onMouseOver={(e: React.MouseEvent<HTMLButtonElement>) => {
        e.currentTarget.setAttribute('title', `${positionName}: ${itemName}`);
      }}
      onFocus={(e: React.FocusEvent<HTMLButtonElement>) => {
        e.currentTarget.setAttribute('title', `${positionName}: ${itemName}`);
      }}
      aria-label={`${positionName}: ${itemName}`}
      aria-labelledby={positionName}
      role="button"
    >
      <img src={path} width={64} alt={altText} />
    </button>
  );
};

export default EquippedSlot;
