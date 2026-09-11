import AreaGemContextDefinition from '../../../../../reusable-components/gems/api/definitions/area-gem-context-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GemWorldIntroductionSidePeekProps extends SidePeekProps {
  character_id: number;
  entry_context: AreaGemContextDefinition;
  entry_can_enter: boolean;
}
