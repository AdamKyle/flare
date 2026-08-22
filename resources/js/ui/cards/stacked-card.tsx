import clsx from 'clsx';
import { motion, useIsPresent, useReducedMotion } from 'framer-motion';
import React from 'react';

import { useStackedCardAccessibility } from './hooks/use-stacked-card-accessibility';
import StackedCardProps from './types/stacked-card-props';

const StackedCard = ({ children, on_close, aria_label }: StackedCardProps) => {
  const isPresent = useIsPresent();
  const reduceMotion = useReducedMotion();

  const { dialogRef, handleKeyDown } = useStackedCardAccessibility({
    active: isPresent,
    on_close,
  });

  const slideVariants = {
    hidden: { x: '100%', opacity: 0 },
    enter: {
      x: 0,
      opacity: 1,
      transition: reduceMotion
        ? { duration: 0 }
        : {
            x: {
              type: 'tween' as const,
              ease: 'easeOut' as const,
              duration: 0.45,
            },
            opacity: { duration: 0.2 },
          },
    },
    exit: {
      x: '100%',
      opacity: 0,
      transition: reduceMotion
        ? { duration: 0 }
        : {
            x: {
              type: 'tween' as const,
              ease: 'easeIn' as const,
              duration: 0.35,
            },
            opacity: { duration: 0.2 },
          },
    },
  };

  return (
    <div
      className={clsx(
        'fixed inset-0 z-[100000] flex items-stretch justify-end',
        isPresent ? 'pointer-events-auto' : 'pointer-events-none'
      )}
    >
      <motion.div
        variants={slideVariants}
        initial="hidden"
        animate="enter"
        exit="exit"
        className={clsx(
          'relative h-full w-full',
          isPresent ? 'pointer-events-auto' : 'pointer-events-none'
        )}
        style={{ willChange: 'transform' }}
      >
        <div
          className="absolute top-4 right-0 bottom-0 left-4 rounded-sm border-1 border-gray-300/40 bg-white/25 dark:border-gray-700/40 dark:bg-gray-900/30"
          aria-hidden
        />

        <div
          className="absolute top-2 right-0 bottom-0 left-2 rounded-sm border-1 border-gray-300/50 bg-white/40 dark:border-gray-700/50 dark:bg-gray-900/45"
          aria-hidden
        />

        <div
          ref={dialogRef}
          tabIndex={-1}
          role="dialog"
          aria-modal="true"
          aria-label={aria_label ?? 'Details'}
          aria-hidden={!isPresent}
          inert={!isPresent}
          onKeyDown={handleKeyDown}
          className="relative h-full overflow-x-hidden overflow-y-auto rounded-sm border-1 border-gray-300 bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        >
          <div
            className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-black/15 to-transparent dark:from-white/10"
            aria-hidden
          />
          <button
            type="button"
            onClick={on_close}
            aria-label="Close details"
            title="Close"
            className="absolute top-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 dark:focus:ring-gray-500"
          >
            <i className="fas fa-times" aria-hidden="true" />
          </button>
          <div className="px-6 pt-12 pb-6">{children}</div>
        </div>
      </motion.div>
    </div>
  );
};

export default StackedCard;
