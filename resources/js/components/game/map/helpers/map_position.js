export const getNewYPosition = (characterY, mapPositionY) => {
  if (characterY < 336) {
    return 0;
  }

  if (characterY === 336) {
    return -304;
  }

  if (characterY < 640) {
    return -304;
  }

  if (characterY === 640) {
    return -608;
  }

  if (characterY < 928) {
    return -608;
  }

  if (characterY === 944) {
    return -900;
  }

  if (characterY < 1248) {
    return -900;
  }

  if (characterY === 1248) {
    return -1212;
  }

  if (characterY < 1552) {
    return -1212;
  }

  if (characterY === 1552) {
    return -1520;
  }

  if (characterY < 1856) {
    return -1520;
  }

  if (characterY === 1856) {
    return -1648;
  }

  return mapPositionY;
}

export const getNewXPosition = (characterX, mapPositionX) => {
  if (characterX < 848) {
    return 0;
  }

  if (characterX > 848) {
    return -464;
  }

  return mapPositionX;
}
