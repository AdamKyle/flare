import MapGeometryDefinition from './definitions/map-geometry-definition';

export const calculateCharacterCenter = (
  characterX: number,
  characterY: number,
  zoom: number
): MapGeometryDefinition => {
  const spriteSize = 16 * zoom;
  const halfSprite = spriteSize / 2;
  const charCenterX = characterX * zoom + halfSprite;
  const charCenterY = characterY * zoom + halfSprite;

  return {
    charCenterX,
    charCenterY,
  };
};
