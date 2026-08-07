import BaseUsableItemDefinition from '../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface UsableItemsProps extends SidePeekProps {
  character_id: number;
  initial_item?: BaseUsableItemDefinition;
}
