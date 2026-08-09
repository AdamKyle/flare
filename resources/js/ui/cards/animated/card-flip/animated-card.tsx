import { motion } from 'framer-motion';
import React, { useState } from 'react';

import AnimatedCardProps from './types/animated-card-props';

const AnimatedCard = ({
  aria_label,
  children,
  is_flipped,
  on_click_card,
}: AnimatedCardProps) => {
  const [internalIsFlipped, setInternalIsFlipped] = useState<boolean>(false);

  const resolvedIsFlipped =
    typeof is_flipped === 'boolean' ? is_flipped : internalIsFlipped;

  const handleClickCard = () => {
    if (on_click_card) {
      on_click_card();

      return;
    }

    setInternalIsFlipped((previousIsFlipped) => !previousIsFlipped);
  };

  return (
    <div
      className="group relative mx-auto h-44 w-full max-w-xs md:h-56"
      style={{ perspective: '1200px' }}
    >
      <button
        type="button"
        onClick={handleClickCard}
        className="absolute inset-0 z-0 block h-full w-full cursor-pointer bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900"
        aria-pressed={resolvedIsFlipped}
        aria-label={aria_label}
      />

      <motion.div
        className="pointer-events-none relative z-10 h-full w-full"
        style={{ transformStyle: 'preserve-3d' }}
        animate={{ rotateY: resolvedIsFlipped ? 180 : 0 }}
        transition={{ duration: 0.45 }}
      >
        {children}
      </motion.div>
    </div>
  );
};

export default AnimatedCard;
