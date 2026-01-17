import { ItemActions } from '../../../../../../reusable-components/item/enums/item-actions';

export default interface UseProcessItemActionRequestParams {
  action_type: ItemActions | null;
  item_id: number | null;
  character_id: number;
  on_success: (message: string) => void;
}
