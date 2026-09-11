import React, { ReactNode } from 'react';

import AllActiveGemScrollsSidePeekProps from './types/all-active-gem-scrolls-side-peek-props';
import AllActiveGemScrollsList from '../../../../reusable-components/gems/progression/components/all-active-gem-scrolls-list';

const AllActiveGemScrollsSidePeek = ({
  character_id: characterId,
}: AllActiveGemScrollsSidePeekProps): ReactNode => (
  <AllActiveGemScrollsList character_id={characterId} />
);

export default AllActiveGemScrollsSidePeek;
