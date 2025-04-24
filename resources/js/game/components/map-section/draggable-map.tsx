import React from 'react';

import DraggableMapProps from './types/draggable-map-props';

import DraggableContainerWrapper from 'ui/draggable/draggable-container';

const DraggableMap = ({ tiles, additional_css }: DraggableMapProps) => {
  return (
    <DraggableContainerWrapper additional_css={additional_css}>
      <div
        className="grid"
        style={{
          gridTemplateColumns: `repeat(${tiles[0].length}, 250px)`,
          gridTemplateRows: `repeat(${tiles.length}, 250px)`,
          lineHeight: 0,
          fontSize: 0,
        }}
      >
        {tiles.flat().map((url, i) => (
          <img
            key={i}
            src={url}
            width={250}
            height={250}
            draggable={false}
            className="block"
            style={{
              display: 'block',
              imageRendering: 'pixelated',
            }}
          />
        ))}
      </div>
    </DraggableContainerWrapper>
  );
};

export default DraggableMap;
