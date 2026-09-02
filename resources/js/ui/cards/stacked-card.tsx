import clsx from 'clsx';
import { motion, useIsPresent, useReducedMotion } from 'framer-motion';
import React from 'react';

import { StackedCardContentMode } from './enums/stacked-card-content-mode';
import { useStackedCardAccessibility } from './hooks/use-stacked-card-accessibility';
import StackedCardProps from './types/stacked-card-props';

const StackedCard = ({
  children,
  on_close,
  aria_label,
  content_mode: contentMode = StackedCardContentMode.PADDED,
}: StackedCardProps) => {
  const isPresent = useIsPresent();
  const reduceMotion = useReducedMotion();
  const isFullBleed = contentMode === StackedCardContentMode.FULL_BLEED;

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
            type: 'tween' as const,
            ease: 'easeOut' as const,
            duration: 0.35,
          },
    },
    exit: {
      x: '100%',
      opacity: 0,
      transition: reduceMotion
        ? { duration: 0 }
        : {
            type: 'tween' as const,
            ease: 'easeIn' as const,
            duration: 0.35,
          },
    },
  };

  return (
    <div
      className={clsx(
        'absolute inset-0 z-40 flex items-stretch justify-end overflow-hidden',
        isPresent ? 'pointer-events-auto' : 'pointer-events-none'
      )}
    >
      <motion.div
        variants={slideVariants}
        initial="hidden"
        animate="enter"
        exit="exit"
        className={clsx(
          'absolute inset-0 h-full w-full',
          isPresent ? 'pointer-events-auto' : 'pointer-events-none'
        )}
        style={{ willChange: 'transform' }}
      >
        <div
          ref={dialogRef}
          tabIndex={-1}
          role="dialog"
          aria-modal="true"
          aria-label={aria_label ?? 'Details'}
          aria-hidden={!isPresent}
          inert={!isPresent}
          onKeyDown={handleKeyDown}
          className={clsx(
            'relative h-full w-full bg-white focus:outline-none dark:bg-gray-800 dark:text-gray-400',
            isFullBleed
              ? 'overflow-hidden rounded-none border-0'
              : 'overflow-x-hidden overflow-y-auto rounded-sm border-1 border-gray-300 dark:border-gray-700'
          )}
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
            className="absolute top-3 right-3 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 dark:focus:ring-gray-500"
          >
            <i className="fas fa-times" aria-hidden="true" />
          </button>
          <div
            className={clsx(
              isFullBleed
                ? 'h-full min-h-0 overflow-hidden pt-12'
                : 'px-6 pt-12 pb-6'
            )}
          >
            {children}
          </div>
        </div>
      </motion.div>
    </div>
  );
};

export default StackedCard;
