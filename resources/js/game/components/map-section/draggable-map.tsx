import React from 'react';

import DraggableMapProps from './types/draggable-map-props';

import DraggableContainerWrapper from 'ui/draggable/draggable-container';

const TILE_SIZE = 250;

const DraggableMap = ({
  tiles,
  additional_css,
  map_icons = [],
  on_click,
  zoom = 1,
}: DraggableMapProps) => {
  const tileSize = TILE_SIZE * zoom;

  const renderMap = () => {
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

  const renderIcons = () => {
    return map_icons.map((icon, id) => (
      <button
        key={id}
        onClick={() => on_click(icon)}
        aria-label={icon.alt || 'Map Icon'}
        className="absolute"
        style={{
          left: `${icon.x * zoom}px`,
          top: `${icon.y * zoom}px`,
          width: 16 * zoom,
          height: 16 * zoom,
          backgroundImage: `url(${icon.src})`,
          backgroundSize: 'contain',
          backgroundRepeat: 'no-repeat',
          backgroundColor: 'transparent',
          border: 'none',
          padding: 0,
          margin: 0,
          cursor: 'pointer',
          imageRendering: 'pixelated',
        }}
      />
    ));
  };

  return (
    <DraggableContainerWrapper additional_css={additional_css}>
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
        {renderMap()}
        {renderIcons()}
      </div>
    </DraggableContainerWrapper>
  );
};

export default DraggableMap;
