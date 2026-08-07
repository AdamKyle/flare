import BaseUsableItemDefinition from '../../../../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface AlchemyResultDefinition {
  item_id: number;
  name: string;
  type: string;
  amount_created: number;
  current_amount: number;
  slot_id: number;
  item_preview: BaseUsableItemDefinition;
}
