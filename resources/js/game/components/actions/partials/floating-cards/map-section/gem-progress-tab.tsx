import React, { ReactNode } from 'react';

import GemProgressTabProps from './types/gem-progress-tab-props';
import GemProgressionPanel from '../../../../../reusable-components/gems/progression/components/gem-progression-panel';

import FloatingCardScreenStack from 'ui/floating-card-screen-stack/floating-card-screen-stack';

const GemProgressTab = ({
  character_id: characterId,
}: GemProgressTabProps): ReactNode => (
  <FloatingCardScreenStack label="Gem Progress">
    <GemProgressionPanel character_id={characterId} />
  </FloatingCardScreenStack>
);

export default GemProgressTab;
