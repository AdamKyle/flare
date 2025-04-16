import React, { ReactNode } from 'react';

import EquippedSlots from "./equipped-slots";
import InventorySectionProps from './types/inventory-section-props';
import { inventoryIconButtons } from './utils/inventory-icon-buttons';

import { MobileIconContainer } from 'ui/icon-container/mobile-icon-container';

const InventorySection = ({
  character_id,
}: InventorySectionProps): ReactNode => {

  return (
    <div className="relative">
      <MobileIconContainer
        icon_buttons={inventoryIconButtons({ character_id: character_id })}
      />

      <div className="flex justify-center">
        <EquippedSlots character_id={character_id} />
      </div>
    </div>
  );
};

export default InventorySection;
