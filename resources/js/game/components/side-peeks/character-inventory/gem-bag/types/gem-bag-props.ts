import BaseGemDetails from '../../../../../api-definitions/items/base-gem-details';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GemBagProps extends SidePeekProps {
  character_id: number;
  initial_gem?: BaseGemDetails;
}
