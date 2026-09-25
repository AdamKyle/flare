import SlotStripCell from '../types/slot-strip-cell';
import SlotSymbolPresentation from '../types/slot-symbol-presentation';

export const SLOT_CELL_HEIGHT = 64;

const SLOT_VIEWPORT_OFFSET = 16;

const SLOT_STRIP_CYCLES = 8;

export const offsetForCell = (cellIndex: number): number =>
  SLOT_VIEWPORT_OFFSET - cellIndex * SLOT_CELL_HEIGHT;

export const cellForOffset = (offset: number): number =>
  (SLOT_VIEWPORT_OFFSET - offset) / SLOT_CELL_HEIGHT;

export const buildSlotStrip = (
  symbols: SlotSymbolPresentation[]
): SlotStripCell[] =>
  Array.from({ length: SLOT_STRIP_CYCLES }).flatMap((_, cycle) =>
    symbols.map((symbol) => ({ key: `${cycle}-${symbol.key}`, symbol }))
  );

/**
 * The final cell must sit at least `minimumCycles` full symbol cycles past the
 * reel's current position so every reel visibly keeps rolling before landing
 * on the server-provided index.
 */
export const resolveLandingCell = (
  currentCell: number,
  targetIndex: number,
  symbolCount: number,
  minimumCycles: number
): number => {
  const minimumCell = Math.ceil(currentCell) + symbolCount * minimumCycles;
  const cyclesToTarget = Math.ceil((minimumCell - targetIndex) / symbolCount);

  return targetIndex + symbolCount * cyclesToTarget;
};
