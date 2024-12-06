import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
import { ServerMessageEnum } from "../../../lib/enums/server-message-enums/server-message-enum";

/**
 * Move the player in a direction.
 *
 * @param positionX
 * @param positionY
 * @param movementDirection
 * @type [{positionX: number, positionY: number, movementDirection: string}]
 */
export const movePlayer = (
    positionX: number,
    positionY: number,
    movementDirection: string,
) => {
    let x = positionX;
    let y = positionY;

    switch (movementDirection) {
        case "north":
            y = y - 16;
            break;
        case "south":
            y = y + 16;
            break;
        case "east":
            x = x + 16;
            break;
        case "west":
            x = x - 16;
            break;
        default:
            break;
    }

    if (y < 16) {
        generateServerMessage(ServerMessageEnum.CANNOT_MOVE_UP);

        return false;
    }

    if (x < 0) {
        generateServerMessage(ServerMessageEnum.CANNOT_MOVE_LEFT);

        return false;
    }

    if (y > 2496) {
        generateServerMessage(ServerMessageEnum.CANNOT_MOVE_DOWN);

        return false;
    }

    if (x > 2496) {
        generateServerMessage(ServerMessageEnum.CANNOT_MOVE_RIGHT);

        return false;
    }

    return {
        x: x,
        y: y,
    };
};
