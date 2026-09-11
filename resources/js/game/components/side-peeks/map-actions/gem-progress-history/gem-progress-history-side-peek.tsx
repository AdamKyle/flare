import React, { ReactNode } from 'react';

import GemProgressHistorySidePeekProps from './types/gem-progress-history-side-peek-props';
import GemProgressHistoryList from '../../../../reusable-components/gems/progression/components/gem-progress-history-list';

const GemProgressHistorySidePeek = ({
  character_id: characterId,
}: GemProgressHistorySidePeekProps): ReactNode => (
  <GemProgressHistoryList character_id={characterId} />
);

export default GemProgressHistorySidePeek;
