import BaseGemDetails from '../../../../api-definitions/items/base-gem-details';
import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { CraftedItemKind } from '../enums/crafted-item-kind';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export interface CraftedInventoryItemProps {
  kind: CraftedItemKind.INVENTORY;
  character_id: number;
  slot_id: number;
}

export interface CraftedUsableItemProps {
  kind: CraftedItemKind.USABLE;
  item: BaseUsableItemDefinition;
}

export interface CraftedGemItemProps {
  kind: CraftedItemKind.GEM;
  gem: BaseGemDetails;
}

type CraftedItemVariant =
  | CraftedInventoryItemProps
  | CraftedUsableItemProps
  | CraftedGemItemProps;

type CraftedItemProps = SidePeekProps & CraftedItemVariant;

export default CraftedItemProps;
