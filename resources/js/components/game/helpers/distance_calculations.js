export const calculateDistance = (xPosition, yPosition, currentX, currentY) => {
  if (xPosition === '' && yPosition === '') {
    return 0;
  }

  const distanceX = Math.pow((xPosition - currentX), 2);
  const distanceY = Math.pow((yPosition - currentY), 2);

  let distance = distanceX + distanceY;
  distance = Math.sqrt(distance);

  if (isNaN(distance)) {
    return 0;
  }

  return Math.round(distance);
}

export const time = (xPosition, yPosition, currentX, currentY) => {
  let time = Math.round(calculateDistance(xPosition, yPosition, currentX, currentY) / 60);

  if (time === 0) {
    return 1;
  }

  return time;
}
