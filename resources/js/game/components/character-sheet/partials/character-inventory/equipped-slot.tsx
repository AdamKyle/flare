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
    <div
      className={`w-16 h-16 text-white flex items-center justify-center border border-gray-600 rounded`}
      onClick={() => {}}
      onMouseOver={(e: React.MouseEvent<HTMLDivElement>) => {
        e.currentTarget.title = `${positionName}: ${itemName}`;
      }}
      aria-label={`${positionName}: ${itemName}`}
    >
      <img src={path} width={64} alt={altText} />
    </div>
  );
};

export default EquippedSlot;
