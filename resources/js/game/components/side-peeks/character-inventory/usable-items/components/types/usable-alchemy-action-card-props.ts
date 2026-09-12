import BaseUsableItemDefinition from '../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface UsableAlchemyActionCardProps {
  item: BaseUsableItemDefinition;
  legal_use_count: number;
  using_slot_id: number | null;
  character_id: number;
  on_click: (itemId: number) => void;
  on_use_one: (slotId: number) => void;
  on_use_quantity: (slotId: number, quantity: number) => void;
  on_use_all: (slotId: number) => void;
  on_gem_scroll_activated: () => void;
}
