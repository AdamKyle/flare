import CharacterGemBagSlotDefinition from '../../../../../api-definitions/gems/character-gem-bag-slot-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GemBagProps extends SidePeekProps {
  character_id: number;
  initial_gem?: CharacterGemBagSlotDefinition;
}
