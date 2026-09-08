import clsx from 'clsx';
import { motion, useIsPresent, useReducedMotion } from 'framer-motion';
import React, {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import StackedCardLayerContextDefinition from 'ui/cards/context/definitions/stacked-card-layer-context-definition';
import StackedCardLayerContext from 'ui/cards/context/stacked-card-layer-context';
import Separator from 'ui/separator/separator';
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

  const [hostElement, setHostElement] = useState<HTMLDivElement | null>(null);
  const activeLayerCountRef = useRef(0);
  const [activeLayerCount, setActiveLayerCount] = useState(0);

  const registerLayer = useCallback((): (() => void) => {
    activeLayerCountRef.current += 1;
    setActiveLayerCount(activeLayerCountRef.current);

    return () => {
      activeLayerCountRef.current = Math.max(
        0,
        activeLayerCountRef.current - 1
      );
      setActiveLayerCount(activeLayerCountRef.current);
    };
  }, []);

  const layerContextValue = useMemo<StackedCardLayerContextDefinition>(
    () => ({ host_element: hostElement, register_layer: registerLayer }),
    [hostElement, registerLayer]
  );

  const isBaseCovered = activeLayerCount > 0;

  useEffect(() => {
    const previousBodyOverflow = document.body.style.overflow;
    const previousBodyPaddingRight = document.body.style.paddingRight;

    const scrollbarWidth =
      window.innerWidth - document.documentElement.clientWidth;
    const computedPaddingRight = parseFloat(
      window.getComputedStyle(document.body).paddingRight
    );

    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = `${computedPaddingRight + scrollbarWidth}px`;

    return () => {
      document.body.style.overflow = previousBodyOverflow;
      document.body.style.paddingRight = previousBodyPaddingRight;
    };
  }, []);

  const panelTransition = reduceMotion
    ? { duration: 0 }
    : { type: 'tween' as const, ease: 'easeOut' as const, duration: 0.3 };

  const backdropTransition = reduceMotion ? { duration: 0 } : { duration: 0.3 };

  return (
    <>
      <motion.div
        className="fixed inset-0 z-[99999] bg-black dark:bg-black/70"
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
          'fixed top-0 right-0 bottom-0 z-[99999] flex h-full flex-col overflow-hidden',
          sidePeekPanelWidthStyles(),
          'bg-white shadow-lg dark:bg-gray-800'
        )}
      >
        <StackedCardLayerContext.Provider value={layerContextValue}>
          <div
            inert={isBaseCovered}
            aria-hidden={isBaseCovered}
            className="flex h-full min-h-0 w-full flex-col"
          >
            <div className="flex items-center justify-between p-4">
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={props.on_close}
                  disabled={!isPresent}
                  className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 rounded px-2 py-1 text-gray-700 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 dark:text-white dark:hover:bg-gray-700"
                  aria-label="Close panel"
                >
                  <i
                    className="fas fa-angle-double-right"
                    aria-hidden="true"
                  ></i>
                </button>
                <h2
                  id="sidepeek-title"
                  className="text-lg font-semibold text-gray-900 dark:text-white"
                >
                  {props.title}
                </h2>
              </div>
            </div>

            <Separator additional_css="my-0" />

            <div className="min-h-0 flex-1 overflow-hidden">
              {props.children}
            </div>

            {props.footer && <Separator additional_css="my-0" />}
            {props.footer}
          </div>

          <div
            ref={setHostElement}
            className="pointer-events-none absolute inset-0 z-10"
          />
        </StackedCardLayerContext.Provider>
      </motion.div>
    </>
  );
};

export default SidePeek;
