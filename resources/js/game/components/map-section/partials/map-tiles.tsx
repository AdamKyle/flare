import React from 'react';

import MapTilesProps from './types/map-tiles-props';
import { MapTileSize } from '../enums/map-tile-size';

const MapTiles = ({ tiles, zoom }: MapTilesProps) => {
  const tileSize = MapTileSize.TILE_SIZE * zoom;

  return tiles.flat().map((url, i) => (
    <img
      key={i}
      src={url}
      draggable={false}
      alt="Map Tile"
      aria-hidden="true"
      style={{
        width: `${tileSize}px`,
        height: `${tileSize}px`,
        display: 'block',
        objectFit: 'cover',
        imageRendering: 'pixelated',
        margin: 0,
        padding: 0,
        border: 'none',
      }}
    />
  ));
};

export default MapTiles;
