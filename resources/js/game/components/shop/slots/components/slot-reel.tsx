import clsx from 'clsx';
import {
  animate,
  motion,
  useMotionValue,
  useReducedMotion,
} from 'framer-motion';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import SlotReelProps from './types/slot-reel-props';
import SlotStripCell from '../types/slot-strip-cell';
import {
  buildSlotStrip,
  cellForOffset,
  offsetForCell,
  resolveLandingCell,
} from '../utils/slot-reel-geometry';

const LOOP_CYCLE_SECONDS = 0.25;
const BASE_STOP_SECONDS = 1.6;
const STOP_STAGGER_SECONDS = 0.5;
const BASE_EXTRA_CYCLES = 2;
const REDUCED_MOTION_SECONDS = 0.15;
const EASE_OUT_CUBIC: [number, number, number, number] = [0.33, 1, 0.68, 1];

const SlotReel = ({
  reel_index,
  symbols,
  is_spinning,
  target_index,
  on_stopped,
}: SlotReelProps): ReactNode => {
  const reduceMotion = useReducedMotion();

  const [restingIndex, setRestingIndex] = useState(0);

  const offset = useMotionValue(offsetForCell(0));
  const opacity = useMotionValue(1);
  const onStoppedRef = useRef(on_stopped);

  const symbolCount = symbols.length;
  const stripCells = useMemo(() => buildSlotStrip(symbols), [symbols]);
  const restingSymbol = symbols[restingIndex];
  const isLooping = is_spinning && target_index === null;

  useEffect(() => {
    onStoppedRef.current = on_stopped;
  }, [on_stopped]);

  useEffect(() => {
    if (!isLooping || reduceMotion || symbolCount === 0) {
      return;
    }

    const currentCell = Math.round(cellForOffset(offset.get())) % symbolCount;
    const loopStart = offsetForCell(currentCell);
    const loopEnd = offsetForCell(currentCell + symbolCount);

    offset.set(loopStart);

    const controls = animate(offset, [loopStart, loopEnd], {
      duration: LOOP_CYCLE_SECONDS,
      ease: 'linear',
      repeat: Infinity,
    });

    return () => {
      controls.stop();
    };
  }, [isLooping, reduceMotion, symbolCount, offset]);

  useEffect(() => {
    if (is_spinning || target_index !== null || symbolCount === 0) {
      return;
    }

    offset.set(offsetForCell(restingIndex));
  }, [is_spinning, target_index, restingIndex, symbolCount, offset]);

  useEffect(() => {
    if (target_index === null || symbolCount === 0) {
      return;
    }

    const restingOffset = offsetForCell(target_index);
    let isActive = true;

    const completeStop = () => {
      if (!isActive) {
        return;
      }

      offset.set(restingOffset);
      setRestingIndex(target_index);
      onStoppedRef.current();
    };

    if (reduceMotion) {
      offset.set(restingOffset);

      const fadeControls = animate(opacity, [0.4, 1], {
        duration: REDUCED_MOTION_SECONDS,
      });

      void fadeControls.then(completeStop);

      return () => {
        isActive = false;
        fadeControls.stop();
      };
    }

    const landingCell = resolveLandingCell(
      cellForOffset(offset.get()),
      target_index,
      symbolCount,
      BASE_EXTRA_CYCLES + reel_index
    );

    const stopControls = animate(offset, offsetForCell(landingCell), {
      duration: BASE_STOP_SECONDS + reel_index * STOP_STAGGER_SECONDS,
      ease: EASE_OUT_CUBIC,
    });

    void stopControls.then(completeStop);

    return () => {
      isActive = false;
      stopControls.stop();
    };
  }, [target_index, symbolCount, reel_index, reduceMotion, offset, opacity]);

  const renderCell = (cell: SlotStripCell): ReactNode => (
    <div
      key={cell.key}
      className="flex h-16 flex-col items-center justify-center gap-0.5"
    >
      <i
        className={clsx(
          cell.symbol.icon_class,
          cell.symbol.color_class,
          'text-2xl'
        )}
        aria-hidden="true"
      />
      <span className="text-xs text-gray-700 dark:text-gray-300">
        {cell.symbol.label}
      </span>
    </div>
  );

  const renderScreenReaderState = (): ReactNode => {
    if (is_spinning) {
      return `Reel ${reel_index + 1} is spinning.`;
    }

    if (!restingSymbol) {
      return null;
    }

    return `Reel ${reel_index + 1} shows ${restingSymbol.label}.`;
  };

  return (
    <div className="flex-1">
      <div
        className="relative h-24 overflow-hidden rounded-md border border-gray-400 bg-white dark:border-gray-600 dark:bg-gray-900"
        aria-hidden="true"
      >
        <motion.div style={{ y: offset, opacity }}>
          {stripCells.map(renderCell)}
        </motion.div>
        <div className="border-marigold-500 dark:border-marigold-400 pointer-events-none absolute inset-x-0 top-4 h-16 border-y-2" />
      </div>
      <span className="sr-only">{renderScreenReaderState()}</span>
    </div>
  );
};

export default SlotReel;
