import { motion, useIsPresent, useReducedMotion } from 'framer-motion';
import React, { ReactNode, useEffect, useId } from 'react';

import MobilePanelProps from './types/mobile-panel-props';

import { useSidePeekAccessibility } from 'ui/side-peek/hooks/use-side-peek-accessibility';

const MobilePanel = ({
  title,
  on_close,
  allow_clicking_outside = true,
  children,
}: MobilePanelProps): ReactNode => {
  const isPresent = useIsPresent();
  const reduceMotion = useReducedMotion();
  const headingId = useId();

  const { dialogRef, handleKeyDown, handleClickingOutside } =
    useSidePeekAccessibility({
      active: isPresent,
      allow_clicking_outside,
      on_close,
    });

  useEffect(() => {
    const previousBodyOverflow = document.body.style.overflow;

    document.body.style.overflow = 'hidden';

    return () => {
      document.body.style.overflow = previousBodyOverflow;
    };
  }, []);

  const panelTransition = reduceMotion
    ? { duration: 0 }
    : { type: 'tween' as const, ease: 'easeOut' as const, duration: 0.25 };

  const backdropTransition = reduceMotion
    ? { duration: 0 }
    : { duration: 0.25 };

  return (
    <>
      <motion.div
        className="fixed inset-0 z-50 bg-black/50 sm:hidden dark:bg-black/70"
        initial={reduceMotion ? false : { opacity: 0 }}
        animate={reduceMotion ? undefined : { opacity: 1 }}
        exit={reduceMotion ? undefined : { opacity: 0 }}
        transition={backdropTransition}
        onClick={handleClickingOutside}
        aria-hidden="true"
      />
      <motion.div
        ref={dialogRef}
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        aria-labelledby={headingId}
        inert={!isPresent}
        onKeyDown={handleKeyDown}
        initial={reduceMotion ? false : { y: '100%' }}
        animate={reduceMotion ? undefined : { y: 0 }}
        exit={reduceMotion ? undefined : { y: '100%' }}
        transition={panelTransition}
        className="fixed right-0 bottom-0 left-0 z-50 max-h-3/4 overflow-y-auto rounded-t-lg bg-white pb-[env(safe-area-inset-bottom)] shadow-lg focus:outline-none sm:hidden dark:bg-gray-800"
      >
        <div className="flex items-center justify-between p-4">
          <h2
            id={headingId}
            className="text-lg font-semibold text-gray-900 dark:text-white"
          >
            {title}
          </h2>
          <button
            type="button"
            onClick={on_close}
            disabled={!isPresent}
            className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 rounded px-2 py-1 text-gray-700 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 dark:text-white dark:hover:bg-gray-700"
            aria-label={`Close ${title} panel`}
          >
            <i className="fas fa-times" aria-hidden="true" />
          </button>
        </div>
        <div className="pb-4">{children}</div>
      </motion.div>
    </>
  );
};

export default MobilePanel;
