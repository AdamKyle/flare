import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';
import BackpackFilterDefinition from '../../../../side-peeks/character-inventory/backpack/types/backpack-filter-definition';
import { InventoryPositionDefinition } from '../enums/equipment-positions';

interface OpenBackpackOptions {
  initial_filters?: BackpackFilterDefinition;
  target_position?: InventoryPositionDefinition;
  on_equipment_changed?: () => void;
}

export const useOpenCharacterBackpack = () => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openBackpack = (options: OpenBackpackOptions = {}) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.BACKPACK,
      {
        is_open: true,
        title: 'Backpack',
        allow_clicking_outside: false,
        initial_filters: options.initial_filters,
        target_position: options.target_position,
        on_equipment_changed: options.on_equipment_changed,
      }
    );
  };

  return {
    openBackpack,
  };
};
