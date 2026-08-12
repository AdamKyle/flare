import React, { ReactNode } from 'react';

import { CraftedItemKind } from './enums/crafted-item-kind';
import CraftedItemProps from './types/crafted-item-props';
import GemDetails from '../character-inventory/gem-bag/gem-details';
import InventoryItem from '../character-inventory/inventory-item/inventory-item';
import UsableItem from '../character-inventory/usable-items/usable-item';

const CraftedItem = (props: CraftedItemProps): ReactNode => {
  if (props.kind === CraftedItemKind.INVENTORY) {
    return (
      <InventoryItem
        character_id={props.character_id}
        slot_id={props.slot_id}
        on_action={() => {}}
      />
    );
  }

  if (props.kind === CraftedItemKind.USABLE) {
    return <UsableItem item={props.item} />;
  }

  return <GemDetails gem={props.gem} />;
};

export default CraftedItem;
