import {getServerMessage} from '../../helpers/server_message';

export const movePlayer = (positionX, positionY, movementDirection) => {
  let x = positionX;
  let y = positionY;

  switch (movementDirection) {
    case 'north':
      y = y - 16;
      break;
    case 'south':
      y = y + 16;
      break;
    case 'east':
      x = x + 16;
      break;
    case 'west':
      x = x - 16;
      break;
    default:
      break;
  }

  if (y < 16) {
    return getServerMessage('cannot_move_up');
  }

  if (x < 0) {
    return getServerMessage('cannot_move_left');
  }

  if (y > 496) {
    return getServerMessage('cannot_move_down');
  }

  if (x > 496) {
    return getServerMessage('cannot_move_right');
  }

  return {
    x: x,
    y: y,
  }
}
