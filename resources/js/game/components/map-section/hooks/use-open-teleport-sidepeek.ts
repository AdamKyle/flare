import UseOpenTeleportModalDefinition from './types/use-open-teleport-modal-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export const UseOpenTeleportSidePeek = (): UseOpenTeleportModalDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openTeleport = (
    character_data: CharacterSheetDefinition,
    character_x: number,
    character_y: number
  ) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.MAP_ACTIONS_TELEPORT,
      {
        is_open: true,
        title: 'Teleport',
        character_data: character_data,
        x: character_x,
        y: character_y,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openTeleport,
  };
};
