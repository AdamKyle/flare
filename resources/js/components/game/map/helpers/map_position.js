export const getNewYPosition = (characterY) => {
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

  return -1648;
}

export const getNewXPosition = (characterX, mapPositionX) => {
  if (characterX <= 736) {
    return 0;
  }

  if (characterX > 1936) {
    return -1245;
  }

  if (characterX > 1536) {
    return -1200;
  }

  if (characterX > 1136) {
    return -800;
  }

  if (characterX > 736) {
    return -400;
  }

  return mapPositionX;
}
