import UseOpenConjureSidePeekDefinition from './definitions/use-open-conjure-side-peek-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export const useOpenConjureSidePeek = (): UseOpenConjureSidePeekDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openConjure = (character_data: CharacterSheetDefinition) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.MAP_ACTIONS_CONJURE,
      {
        is_open: true,
        title: 'Conjure',
        character_data: character_data,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openConjure,
  };
};
