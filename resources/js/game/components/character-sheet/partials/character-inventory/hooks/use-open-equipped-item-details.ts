import UseOpenEquippedItemDetailsDefinition from './definition/use-open-equipped-item-details-definition';
import UseOpenEquippedItemDetailsProps from './types/use-open-equipped-item-details-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';
import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export const useOpenEquippedItemDetails = ({
  character_id,
  on_equipment_changed,
}: UseOpenEquippedItemDetailsProps): UseOpenEquippedItemDetailsDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openEquippedItemDetails = (
    equippedItem: BaseInventoryItemDefinition
  ) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.EQUIPPED_ITEM_DETAILS,
      {
        is_open: true,
        title: equippedItem.name,
        character_id,
        slot_id: equippedItem.slot_id,
        item_type: equippedItem.type,
        position: equippedItem.position,
        item_name: equippedItem.name,
        on_equipment_changed,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openEquippedItemDetails,
  };
};
