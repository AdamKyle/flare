import React from 'react';

import DraggableMapProps from './types/draggable-map-props';

import DraggableContainerWrapper from 'ui/draggable/draggable-container';

const DraggableMap = ({
  tiles,
  additional_css,
  map_icons = [],
  on_click,
}: DraggableMapProps) => {
  const renderMap = () => {
    return tiles.flat().map((url, i) => (
      <img
        key={i}
        src={url}
        width={250}
        height={250}
        draggable={false}
        alt="Map Tile"
        aria-hidden="true"
        className="block"
        style={{
          display: 'block',
          imageRendering: 'pixelated',
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
          left: `${icon.x}px`,
          top: `${icon.y}px`,
          width: 32,
          height: 32,
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
          gridTemplateColumns: `repeat(${tiles[0].length}, 250px)`,
          gridTemplateRows: `repeat(${tiles.length}, 250px)`,
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
