import React from 'react';

import CharacterPinProps from './types/character-pin-props';

const CharacterPin = ({ character, zoom }: CharacterPinProps) => {
  return (
    <img
      className="absolute"
      src={character.src}
      alt={character.alt}
      draggable={false}
      style={{
        left: `${character.x * zoom}px`,
        top: `${character.y * zoom}px`,
        width: `${16 * zoom}px`,
        height: `${16 * zoom}px`,
        imageRendering: 'pixelated',
        backgroundColor: 'transparent',
        backgroundSize: 'contain',
        backgroundRepeat: 'no-repeat',
        border: 'none',
        padding: 0,
        margin: 0,
        zIndex: 1,
      }}
    />
  );
};

export default CharacterPin;
