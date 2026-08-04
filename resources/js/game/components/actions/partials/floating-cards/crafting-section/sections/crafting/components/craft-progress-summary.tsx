import React, { ReactNode } from 'react';

import CraftProgressSummaryProps from './types/craft-progress-summary-props';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';

const CraftProgressSummary = ({
  craftingData,
}: CraftProgressSummaryProps): ReactNode => {
  if (!craftingData) {
    return null;
  }

  return (
    <div className="my-2 mb-4">
      <div className="my-2">
        <CraftingSkillXpProgress xp={craftingData.xp} />
      </div>
      <div>
        <CraftingInventoryProgress
          inventory_count={craftingData.inventory_count}
        />
      </div>
    </div>
  );
};

export default CraftProgressSummary;
