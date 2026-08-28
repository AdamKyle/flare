import React, { ReactNode } from 'react';

import { MapTileSize } from 'game-utils/map-tile-size';

import GameMapTileLayerProps from '../types/game-map-tile-layer-props';

const GameMapTileLayer = ({ tiles }: GameMapTileLayerProps): ReactNode => (
  <>
    {tiles.map((row, rowIndex) =>
      row.map((tileUrl, columnIndex) => (
        <img
          key={`${rowIndex}-${columnIndex}`}
          src={tileUrl}
          draggable={false}
          alt=""
          aria-hidden="true"
          className="pointer-events-none absolute m-0 block h-[250px] w-[250px] border-0 object-cover p-0 [image-rendering:pixelated]"
          style={{
            left: columnIndex * MapTileSize.TILE_SIZE,
            top: rowIndex * MapTileSize.TILE_SIZE,
          }}
        />
      ))
    )}
  </>
);

export default GameMapTileLayer;
