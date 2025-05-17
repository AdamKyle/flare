import React from 'react';

import MapLocationProps from './types/map-location-props';

const MapLocations = ({ mapIcons, onClick, zoom }: MapLocationProps) => {
  return mapIcons.map((icon, id) => (
    <button
      key={id}
      onClick={() => onClick(icon)}
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

export default MapLocations;
