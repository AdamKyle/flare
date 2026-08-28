import React, { ReactNode } from 'react';

import GameMapCoordinateGridLayerProps from '../types/game-map-coordinate-grid-layer-props';

const GameMapCoordinateGridLayer = ({
  x_values: xValues,
  y_values: yValues,
  map_width: mapWidth,
  map_height: mapHeight,
}: GameMapCoordinateGridLayerProps): ReactNode => (
  <svg
    aria-hidden="true"
    className="pointer-events-none absolute top-0 left-0 stroke-gray-400/60 dark:stroke-gray-500/60"
    width={mapWidth}
    height={mapHeight}
  >
    {xValues.map((value) => (
      <line
        key={`x-${value}`}
        x1={value}
        y1={0}
        x2={value}
        y2={mapHeight}
        strokeDasharray="2,2"
        strokeWidth={1}
      />
    ))}
    {yValues.map((value) => (
      <line
        key={`y-${value}`}
        x1={0}
        y1={value}
        x2={mapWidth}
        y2={value}
        strokeDasharray="2,2"
        strokeWidth={1}
      />
    ))}
  </svg>
);

export default GameMapCoordinateGridLayer;
