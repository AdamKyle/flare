import React, { ReactNode } from 'react';

import GameMapSelectedCellLayerProps from '../types/game-map-selected-cell-layer-props';

const GameMapSelectedCellLayer = ({
  highlighted,
}: GameMapSelectedCellLayerProps): ReactNode => {
  if (!highlighted) {
    return null;
  }

  return (
    <div
      aria-hidden="true"
      className="border-mango-tango-400 pointer-events-none absolute border-2"
      style={{
        left: highlighted.left,
        top: highlighted.top,
        width: highlighted.width,
        height: highlighted.height,
      }}
    />
  );
};

export default GameMapSelectedCellLayer;
