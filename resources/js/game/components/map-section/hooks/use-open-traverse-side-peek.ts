import UseOpenTraverseModalDefinition from './types/use-open-traverse-modal-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export const UseOpenTraverseSidePeek = (): UseOpenTraverseModalDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openTraverse = (character_data: CharacterSheetDefinition) => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.MAP_ACTIONS_TRAVERSE,
      {
        is_open: true,
        title: 'Traverse',
        character_data: character_data,
      }
    );
  };

  return {
    openTraverse,
  };
};
