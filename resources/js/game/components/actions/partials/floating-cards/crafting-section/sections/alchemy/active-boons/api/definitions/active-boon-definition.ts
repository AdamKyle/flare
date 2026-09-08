import BaseUsableItemDefinition from '../../../../../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface ActiveBoonDefinition {
  id: number;
  character_id: number;
  item_id: number;
  last_for_minutes: number;
  amount_used: number;
  started: string;
  complete: string;
  amount_left: number;
  boon_applied: BaseUsableItemDefinition;
}
