import {
  AnimatePresence,
  motion,
  useIsPresent,
  useReducedMotion,
} from 'framer-motion';
import React, { ReactNode, useEffect, useRef } from 'react';

import CraftingScreenTransitionProps from './types/crafting-screen-transition-props';

const Screen = ({
  label,
  children,
}: Omit<CraftingScreenTransitionProps, 'screenKey'>): ReactNode => {
  const isPresent = useIsPresent();
  const reduceMotion = useReducedMotion();
  const screenRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!isPresent) {
      return;
    }

    screenRef.current?.focus({ preventScroll: true });
  }, [isPresent]);

  return (
    <motion.div
      ref={screenRef}
      role="region"
      aria-label={label}
      aria-hidden={!isPresent}
      inert={!isPresent}
      tabIndex={isPresent ? -1 : undefined}
      initial={reduceMotion ? false : { x: '100%' }}
      animate={reduceMotion ? undefined : { x: 0 }}
      exit={reduceMotion ? undefined : { x: '-100%' }}
      transition={reduceMotion ? { duration: 0 } : { duration: 0.25 }}
      className="w-full focus:outline-none"
    >
      {children}
    </motion.div>
  );
};

const CraftingScreenTransition = ({
  screenKey,
  label,
  children,
}: CraftingScreenTransitionProps): ReactNode => (
  <div className="relative overflow-hidden">
    <AnimatePresence mode="wait" initial={false}>
      <Screen key={screenKey} label={label}>
        {children}
      </Screen>
    </AnimatePresence>
  </div>
);

export default CraftingScreenTransition;
