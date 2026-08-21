import clsx from 'clsx';
import { motion, useIsPresent, useReducedMotion } from 'framer-motion';
import React, { useEffect } from 'react';

import { useSidePeekAccessibility } from 'ui/side-peek/hooks/use-side-peek-accessibility';
import { sidePeekPanelWidthStyles } from 'ui/side-peek/styles/side-peek-panel-styles';
import SidePeekProps from 'ui/side-peek/types/side-peek-props';

const SidePeek = (props: SidePeekProps) => {
  const isPresent = useIsPresent();
  const reduceMotion = useReducedMotion();

  const { dialogRef, handleKeyDown, handleClickingOutside } =
    useSidePeekAccessibility({
      active: isPresent,
      allow_clicking_outside: props.allow_clicking_outside,
      on_close: props.on_close,
    });

  useEffect(() => {
    document.body.classList.add('body-no-scroll');

    return () => {
      document.body.classList.remove('body-no-scroll');
    };
  }, []);

  const panelTransition = reduceMotion
    ? { duration: 0 }
    : { type: 'tween' as const, ease: 'easeOut' as const, duration: 0.3 };

  const backdropTransition = reduceMotion ? { duration: 0 } : { duration: 0.3 };

  return (
    <>
      <motion.div
        className="fixed inset-0 z-40 z-[99999] bg-black dark:bg-black/70"
        initial={reduceMotion ? false : { opacity: 0 }}
        animate={reduceMotion ? undefined : { opacity: 0.5 }}
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
        aria-labelledby="sidepeek-title"
        aria-hidden={!isPresent}
        inert={!isPresent}
        onKeyDown={handleKeyDown}
        initial={reduceMotion ? false : { x: '100%' }}
        animate={reduceMotion ? undefined : { x: 0 }}
        exit={reduceMotion ? undefined : { x: '100%' }}
        transition={panelTransition}
        className={clsx(
          'fixed top-0 right-0 z-50 h-full',
          sidePeekPanelWidthStyles(),
          'bg-white shadow-lg dark:bg-gray-800',
          'position-static z-[99999] flex flex-col'
        )}
      >
        <div className="flex items-center justify-between border-b p-4 dark:border-gray-700">
          <div className="flex items-center gap-2">
            <button
              onClick={props.on_close}
              disabled={!isPresent}
              className="rounded px-2 py-1 text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              aria-label="Close panel"
            >
              <i className="fas fa-angle-double-right" aria-hidden="true"></i>
            </button>
            <h2
              id="sidepeek-title"
              className="text-lg font-semibold text-gray-900 dark:text-white"
            >
              {props.title}
            </h2>
          </div>
        </div>

        <div className="min-h-0 flex-1">{props.children}</div>
      </motion.div>
    </>
  );
};

export default SidePeek;
