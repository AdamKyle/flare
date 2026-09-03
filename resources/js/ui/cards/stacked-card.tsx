import clsx from 'clsx';
import { motion, useIsPresent, useReducedMotion } from 'framer-motion';
import React, {
  ReactNode,
  useCallback,
  useEffect,
  useId,
  useMemo,
  useRef,
  useState,
} from 'react';
import { createPortal } from 'react-dom';

import StackedCardLayerContextDefinition from './context/definitions/stacked-card-layer-context-definition';
import { useStackedCardLayerContext } from './context/hooks/use-stacked-card-layer-context';
import StackedCardLayerContext from './context/stacked-card-layer-context';
import { StackedCardContentMode } from './enums/stacked-card-content-mode';
import { useStackedCardAccessibility } from './hooks/use-stacked-card-accessibility';
import StackedCardProps from './types/stacked-card-props';

const StackedCard = ({
  children,
  on_close: onClose,
  aria_label: ariaLabel,
  content_mode: contentMode = StackedCardContentMode.PADDED,
}: StackedCardProps) => {
  const isPresent = useIsPresent();
  const reduceMotion = useReducedMotion();
  const isFullBleed = contentMode === StackedCardContentMode.FULL_BLEED;
  const titleId = useId();
  const displayTitle = ariaLabel ?? 'Details';

  const parentLayerContext = useStackedCardLayerContext();

  const { dialogRef, handleKeyDown } = useStackedCardAccessibility({
    active: isPresent,
    on_close: onClose,
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

  const ownLayerContextValue = useMemo<StackedCardLayerContextDefinition>(
    () => ({ host_element: hostElement, register_layer: registerLayer }),
    [hostElement, registerLayer]
  );

  useEffect(() => {
    if (!isFullBleed || !parentLayerContext?.host_element) {
      return;
    }

    return parentLayerContext.register_layer();
  }, [isFullBleed, parentLayerContext]);

  const handleDialogKeyDown = (
    event: React.KeyboardEvent<HTMLDivElement>
  ): void => {
    event.stopPropagation();
    handleKeyDown(event);
  };

  const isCoveredByChildLayer = activeLayerCount > 0;

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

  const renderFullBleedHeader = (): ReactNode => (
    <div className="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
      <h2
        id={titleId}
        className="text-glacier-900 dark:text-glacier-100 text-base font-semibold"
      >
        {displayTitle}
      </h2>
      <button
        type="button"
        onClick={onClose}
        aria-label={`Close ${displayTitle}`}
        className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex h-9 w-9 items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 dark:text-gray-300 dark:hover:bg-gray-700"
      >
        <i className="fas fa-times" aria-hidden="true" />
      </button>
    </div>
  );

  const renderPaddedChrome = (): ReactNode => (
    <>
      <div
        className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-black/15 to-transparent dark:from-white/10"
        aria-hidden
      />
      <button
        type="button"
        onClick={onClose}
        aria-label="Close details"
        title="Close"
        className="absolute top-3 right-3 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 dark:focus:ring-gray-500"
      >
        <i className="fas fa-times" aria-hidden="true" />
      </button>
    </>
  );

  const renderContentRegion = (): ReactNode => (
    <div
      className={clsx(
        isFullBleed ? 'min-h-0 flex-1 overflow-hidden' : 'px-6 pt-12 pb-6'
      )}
    >
      {children}
    </div>
  );

  const renderCard = (): ReactNode => (
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
          'absolute inset-0 h-full w-full will-change-transform',
          isPresent ? 'pointer-events-auto' : 'pointer-events-none'
        )}
      >
        <StackedCardLayerContext.Provider value={ownLayerContextValue}>
          <div
            ref={dialogRef}
            tabIndex={-1}
            role="dialog"
            aria-modal="true"
            aria-label={isFullBleed ? undefined : displayTitle}
            aria-labelledby={isFullBleed ? titleId : undefined}
            aria-hidden={!isPresent || isCoveredByChildLayer}
            inert={!isPresent || isCoveredByChildLayer}
            onKeyDown={handleDialogKeyDown}
            className={clsx(
              'relative flex h-full w-full flex-col bg-white focus:outline-none dark:bg-gray-800 dark:text-gray-400',
              isFullBleed
                ? 'overflow-hidden rounded-none border-0'
                : 'overflow-x-hidden overflow-y-auto rounded-sm border-1 border-gray-300 dark:border-gray-700'
            )}
          >
            {isFullBleed ? renderFullBleedHeader() : renderPaddedChrome()}
            {renderContentRegion()}
          </div>

          <div
            ref={setHostElement}
            className="pointer-events-none absolute inset-0 z-50"
          />
        </StackedCardLayerContext.Provider>
      </motion.div>
    </div>
  );

  if (isFullBleed && parentLayerContext?.host_element) {
    return createPortal(renderCard(), parentLayerContext.host_element);
  }

  return renderCard();
};

export default StackedCard;
