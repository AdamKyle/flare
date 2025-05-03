import React, { ReactNode } from 'react';

import EquippedSlots from './equipped-slots';
import { useOpenCharacterBackpack } from './hooks/use-open-character-backpack';
import { useOpenCharacterGemBag } from './hooks/use-open-character-gem-bag';
import { useOpenCharacterSets } from './hooks/use-open-character-sets';
import { useOpenCharacterUsableInventory } from './hooks/use-open-character-usable-inventory';
import InventorySectionProps from './types/inventory-section-props';
import { inventoryIconButtons } from './utils/inventory-icon-buttons';

import { MobileIconContainer } from 'ui/icon-container/mobile-icon-container';

const InventorySection = ({
  character_id,
}: InventorySectionProps): ReactNode => {
  const { openBackpack } = useOpenCharacterBackpack({ character_id });
  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id,
  });
  const { openGemBag } = useOpenCharacterGemBag({ character_id });
  const { openSets } = useOpenCharacterSets({ character_id });

  return (
    <div className="relative">
      <MobileIconContainer
        icon_buttons={inventoryIconButtons({
          openBackpack,
          openUsableInventory,
          openGemBag,
          openSets,
        })}
      />

      <div className="flex justify-center">
        <EquippedSlots character_id={character_id} />
      </div>
    </div>
  );
};

export default InventorySection;
