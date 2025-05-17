import React from 'react';

import { MapTileSize } from './enums/map-tile-size';
import DraggableMapProps from './types/draggable-map-props';
import { calculateCharacterCenter } from './utils/map-geometry';

import DraggableContainerWrapper from 'ui/draggable/draggable-container';

const DraggableMap = ({
  additional_css,
  zoom = 1,
  character,
  children,
  tiles,
}: DraggableMapProps) => {
  const tileSize = MapTileSize.TILE_SIZE * zoom;

  const characterCenter = calculateCharacterCenter(
    character.x,
    character.y,
    zoom
  );

  return (
    <DraggableContainerWrapper
      additional_css={additional_css}
      center_on_x={characterCenter.charCenterX}
      center_on_y={characterCenter.charCenterY}
    >
      <div
        role="group"
        aria-label="Map grid"
        className="grid"
        style={{
          gridTemplateColumns: `repeat(${tiles[0].length}, ${tileSize}px)`,
          gridTemplateRows: `repeat(${tiles.length}, ${tileSize}px)`,
          gap: 0,
          lineHeight: 0,
          fontSize: 0,
        }}
      >
        {children}
      </div>
    </DraggableContainerWrapper>
  );
};

export default DraggableMap;
