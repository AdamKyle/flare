import React from 'react';

import BaseGemDetails from '../../../../../api-definitions/items/base-gem-details';

export default interface GemListProps {
  gems: BaseGemDetails[];
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
}
