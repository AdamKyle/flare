import ItemFormDefinition from '../../../api/definitions/item-form-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface ItemFormSidePeekProps extends SidePeekProps {
  item_id: number | null;
  on_saved: (item: ItemFormDefinition) => void;
}
