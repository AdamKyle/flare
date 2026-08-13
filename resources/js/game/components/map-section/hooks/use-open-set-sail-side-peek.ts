import UseOpenSetSailSidePeekDefinition from './definitions/use-open-set-sail-side-peek-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export const useOpenSetSailSidePeek = (): UseOpenSetSailSidePeekDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openSetSail = (character_data: CharacterSheetDefinition) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.MAP_ACTIONS_SET_SAIL,
      {
        is_open: true,
        title: 'Set Sail',
        character_data: character_data,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openSetSail,
  };
};
