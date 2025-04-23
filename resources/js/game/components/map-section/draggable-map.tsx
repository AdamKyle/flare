import React from 'react';

import DraggableMapProps from './types/draggable-map-props';

import DraggableContainerWrapper from 'ui/draggable/draggable-container';

const DraggableMap = ({
  tiles,
  containerWidth,
  containerHeight,
}: DraggableMapProps) => {
  return (
    <DraggableContainerWrapper width={containerWidth} height={containerHeight}>
      <div
        className="grid"
        style={{
          gridTemplateColumns: `repeat(${tiles[0].length}, 250px)`,
          gridTemplateRows: `repeat(${tiles.length}, 250px)`,
        }}
      >
        {tiles.flat().map((url: string, i: number) => (
          <img
            key={i}
            src={url}
            width={250}
            height={250}
            className="block"
            draggable={false}
          />
        ))}
      </div>
    </DraggableContainerWrapper>
  );
};

export default DraggableMap;
