export const getNewYPosition = (characterY, mapPositionY) => {
  if (characterY < 320) {
    return 0;
  }

  if (characterY > 320) {
    return -150;
  }

  return mapPositionY;
}

export const getNewXPosition = (characterX, mapPositionX) => {
  if (characterX <= 368) {
    return 0;
  }

  if (characterX > 368) {
    return -100;
  }

  return mapPositionX;
}
