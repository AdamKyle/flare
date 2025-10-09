import { motion } from 'framer-motion';
import React from 'react';

import StackedCardProps from './types/stacked-card-props';

const slideVariants = {
  hidden: { x: '100%', opacity: 0 },
  enter: {
    x: 0,
    opacity: 1,
    transition: {
      x: { type: 'tween', ease: 'easeOut', duration: 0.45 },
      opacity: { duration: 0.2 },
    },
  },
  exit: {
    x: '100%',
    opacity: 0,
    transition: {
      x: { type: 'tween', ease: 'easeIn', duration: 0.35 },
      opacity: { duration: 0.2 },
    },
  },
};

const StackedCard = ({ children, on_close }: StackedCardProps) => {
  return (
    <div className="fixed inset-0 z-50 flex items-stretch justify-start pointer-events-none">
      <motion.div
        variants={slideVariants}
        initial="hidden"
        animate="enter"
        exit="exit"
        className="pointer-events-auto relative h-full w-full max-w-xl"
        style={{ willChange: 'transform' }}
      >
        <div
          className="absolute top-4 left-4 right-0 bottom-0 rounded-sm bg-white/25 dark:bg-gray-900/30 border-1 border-gray-300/40 dark:border-gray-700/40"
          aria-hidden
        />

        <div
          className="absolute top-2 left-2 right-0 bottom-0 rounded-sm bg-white/40 dark:bg-gray-900/45 border-1 border-gray-300/50 dark:border-gray-700/50"
          aria-hidden
        />

        <div className="relative h-full bg-white rounded-sm dark:bg-gray-800 dark:text-gray-400 border-1 border-gray-300 dark:border-gray-700 overflow-y-auto">
          <div
            className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-black/15 to-transparent dark:from-white/10"
            aria-hidden
          />
          <button
            type="button"
            onClick={on_close}
            aria-label="Close item details"
            title="Close"
            className="absolute right-3 top-3 h-9 w-9 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 dark:focus:ring-gray-500 flex items-center justify-center text-gray-800 dark:text-gray-100"
          >
            <i className="fas fa-times" aria-hidden="true" />
          </button>
          <div className="px-6 pb-6 pt-12">{children}</div>
        </div>
      </motion.div>
    </div>
  );
};

export default StackedCard;
