import React from 'react';

import CharacterGemBagSlotDefinition from '../../../../../api-definitions/gems/character-gem-bag-slot-definition';

export default interface GemListProps {
  gems: CharacterGemBagSlotDefinition[];
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
  on_view_gem: (slotId: number) => void;
}
