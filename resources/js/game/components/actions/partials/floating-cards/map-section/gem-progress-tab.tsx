import React, { ReactNode } from 'react';

import GemProgressTabProps from './types/gem-progress-tab-props';
import GemProgressionPanel from '../../../../../reusable-components/gems/progression/components/gem-progression-panel';

const GemProgressTab = ({
  character_id: characterId,
}: GemProgressTabProps): ReactNode => (
  <GemProgressionPanel character_id={characterId} />
);

export default GemProgressTab;
