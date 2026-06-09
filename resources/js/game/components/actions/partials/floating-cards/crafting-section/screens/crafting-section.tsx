import { AnimatePresence, motion } from 'framer-motion';
import React from 'react';

import CraftItemsFlow from '../components/craft-items-flow';
import CraftingIntroduction from '../components/crafting-introduction';
import { useCraftingIntroduction } from '../hooks/use-crafting-introduction';
import BaseSectionProps from './types/base-section-props';

const CraftingSection = ({ setActiveCraftingType }: BaseSectionProps) => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingIntroduction();

  return (
    <div className="relative overflow-hidden">
      <AnimatePresence mode="wait" initial={false}>
        <motion.div
          key={introductionAcknowledged ? 'craft-items' : 'introduction'}
          initial={{ x: '100%' }}
          animate={{ x: 0 }}
          exit={{ x: '-100%' }}
          transition={{ duration: 0.25 }}
        >
          {introductionAcknowledged ? (
            <CraftItemsFlow setActiveCraftingType={setActiveCraftingType} />
          ) : (
            <CraftingIntroduction onAcknowledge={acknowledgeIntroduction} />
          )}
        </motion.div>
      </AnimatePresence>
    </div>
  );
};

export default CraftingSection;
