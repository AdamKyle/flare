import { useReactFlow } from '@xyflow/react';
import { useReducedMotion } from 'framer-motion';
import React, { ReactNode, useEffect, useState } from 'react';

import TreeControlsProps from '../types/tree-controls-props';

const CONTROL_BUTTON_CLASSES =
  'nodrag nopan flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-danube-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus-visible:ring-danube-300';

/**
 * Generic Flare-styled Tree viewport controls: zoom in, zoom out, fit tree,
 * and fullscreen. Replaces React Flow's default styled `Controls` component
 * so light, dark, and Tailwind presentation stays owned by Flare. `nodrag
 * nopan` prevents a control click from also starting a pane-pan gesture.
 * Fullscreen uses the standard browser Fullscreen API directly against the
 * Tree's own container element (`container_ref`), never the whole page, and
 * never changes zoom/position on its own — entering/exiting fullscreen only
 * resizes the container that React Flow already renders into.
 */
const TreeControls = ({
  container_ref: containerRef,
  on_fullscreen_change: onFullscreenChange,
}: TreeControlsProps): ReactNode => {
  const { zoomIn, zoomOut, fitView } = useReactFlow();
  const reduceMotion = useReducedMotion();
  const duration = reduceMotion ? 0 : 200;
  const [isFullscreen, setIsFullscreen] = useState(false);

  const fullscreenSupported =
    typeof document !== 'undefined' && document.fullscreenEnabled;

  useEffect(() => {
    const handleFullscreenChange = (): void => {
      const isCurrentlyFullscreen =
        document.fullscreenElement === containerRef.current;

      setIsFullscreen(isCurrentlyFullscreen);
      onFullscreenChange?.(isCurrentlyFullscreen);
    };

    document.addEventListener('fullscreenchange', handleFullscreenChange);

    return () => {
      document.removeEventListener('fullscreenchange', handleFullscreenChange);
    };
  }, [containerRef, onFullscreenChange]);

  const handleZoomIn = (): void => {
    void zoomIn({ duration });
  };

  const handleZoomOut = (): void => {
    void zoomOut({ duration });
  };

  const handleFitTree = (): void => {
    void fitView({ duration });
  };

  const handleToggleFullscreen = (): void => {
    if (isFullscreen) {
      void document.exitFullscreen();

      return;
    }

    void containerRef.current?.requestFullscreen();
  };

  const renderFullscreenControl = (): ReactNode => {
    if (!fullscreenSupported) {
      return null;
    }

    return (
      <button
        type="button"
        onClick={handleToggleFullscreen}
        aria-label={isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
        className={CONTROL_BUTTON_CLASSES}
      >
        <i
          className={
            isFullscreen
              ? 'fas fa-compress-arrows-alt'
              : 'fas fa-expand-arrows-alt'
          }
          aria-hidden="true"
        />
      </button>
    );
  };

  return (
    <div className="absolute right-2 bottom-2 z-10 flex flex-col gap-1">
      <button
        type="button"
        onClick={handleZoomIn}
        aria-label="Zoom in"
        className={CONTROL_BUTTON_CLASSES}
      >
        <i className="fas fa-plus" aria-hidden="true" />
      </button>
      <button
        type="button"
        onClick={handleZoomOut}
        aria-label="Zoom out"
        className={CONTROL_BUTTON_CLASSES}
      >
        <i className="fas fa-minus" aria-hidden="true" />
      </button>
      <button
        type="button"
        onClick={handleFitTree}
        aria-label="Fit tree"
        className={CONTROL_BUTTON_CLASSES}
      >
        <i className="fas fa-expand" aria-hidden="true" />
      </button>
      {renderFullscreenControl()}
    </div>
  );
};

export default TreeControls;
