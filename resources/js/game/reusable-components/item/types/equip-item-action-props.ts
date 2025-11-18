import { ItemComparison } from '../../../api-definitions/items/item-comparison-details';
import UsePurchaseAndReplaceApiRequestDefinition from '../../../components/shop/api/hooks/definitions/use-purchase-and-replace-api-request-definition';

export default interface EquipItemActionProps {
  comparison_details: ItemComparison;
  on_confirm_action: (
    requestParams: UsePurchaseAndReplaceApiRequestDefinition
  ) => void;
  on_close_equip_action?: () => void;
  is_processing: boolean;
  is_equipping?: boolean;
}
