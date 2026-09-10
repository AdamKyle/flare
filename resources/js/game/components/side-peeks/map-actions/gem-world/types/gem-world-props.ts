import AreaGemContextDefinition from '../../../../../reusable-components/gems/api/definitions/area-gem-context-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GemWorldProps extends SidePeekProps {
  character_id: number;
  context: AreaGemContextDefinition;
  can_enter: boolean;
}
