import UseOpenTeleportModalDefinition from './types/use-open-teleport-modal-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const UseOpenTeleportSidePeek = (): UseOpenTeleportModalDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openTeleport = (
    character_id: number,
    character_x: number,
    character_y: number
  ) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.MAP_ACTIONS_TELEPORT,
      {
        is_open: true,
        title: 'Teleport',
        character_id: character_id,
        x: character_x,
        y: character_y,
        allow_clicking_outside: true,
        footer_primary_label: 'Teleport',
        footer_primary_action: () => {},
        footer_secondary_label: 'Cancel',
        footer_secondary_action: () => {},
        has_footer: true,
      }
    );
  };

  return {
    openTeleport,
  };
};
