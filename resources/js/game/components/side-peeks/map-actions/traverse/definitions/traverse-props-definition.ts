import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface TraversePropsDefinition extends SidePeekProps {
  character_data: CharacterSheetDefinition;
}
