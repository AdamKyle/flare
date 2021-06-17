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

export const dragMap = (position, bottomBounds, rightBounds) => {
  const {x, y} = position;
  const yBounds = Math.sign(position.y);
  const xBounds = Math.sign(position.x);
  let bottomMapBounds = bottomBounds;
  let rightMapBounds = rightBounds;

  if (yBounds === -1) {
    bottomMapBounds += Math.abs(yBounds);
  } else {
    bottomMapBounds = 0;
  }

  if (xBounds === -1) {
    rightMapBounds += Math.abs(xBounds);
  } else {
    rightMapBounds = 0;
  }

  return {
    controlledPosition: {x, y},
    bottomBounds: bottomBounds,
    rightBounds: rightBounds,
  }
}
