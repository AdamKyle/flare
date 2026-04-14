import { AnimatePresence, motion } from 'framer-motion';
import React, { ReactNode, useState } from 'react';

import { ScreenMapper } from './component-mapping/screen-registery';
import { CraftingTypes } from './enums/crafting-types';
import { useManageCraftingCardVisibility } from './hooks/use-manage-crafting-card-visibility';
import FloatingCard from '../../../components/icon-section/floating-card';

const CraftingCard = (): ReactNode => {
  const { closeCraftingCard } = useManageCraftingCardVisibility();

  const [activeCraftingType, setActiveCraftingType] = useState<CraftingTypes>(
    CraftingTypes.HOME
  );

  const ActiveScreen = ScreenMapper[activeCraftingType];

  const renderBackAction = () => {
    if (activeCraftingType === CraftingTypes.HOME) {
      return;
    }

    return setActiveCraftingType(CraftingTypes.HOME);
  };

  if (!ActiveScreen) {
    return null;
  }

  return (
    <FloatingCard
      title={activeCraftingType}
      close_action={closeCraftingCard}
      back_action={
        activeCraftingType === CraftingTypes.HOME ? undefined : renderBackAction
      }
    >
      <div className="relative overflow-hidden">
        <AnimatePresence mode="wait" initial={false}>
          <motion.div
            key={activeCraftingType}
            initial={{ x: '100%' }}
            animate={{ x: 0 }}
            exit={{ x: '-100%' }}
            transition={{ duration: 0.25 }}
            className="w-full"
          >
            <ActiveScreen setActiveCraftingType={setActiveCraftingType} />
          </motion.div>
        </AnimatePresence>
      </div>
    </FloatingCard>
  );
};

export default CraftingCard;
