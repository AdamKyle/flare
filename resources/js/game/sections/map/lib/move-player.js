import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
export var movePlayer = function (positionX, positionY, movementDirection) {
    var x = positionX;
    var y = positionY;
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
        generateServerMessage("cannot_move_up");
        return false;
    }
    if (x < 0) {
        generateServerMessage("cannot_move_left");
        return false;
    }
    if (y > 2496) {
        generateServerMessage("cannot_move_down");
        return false;
    }
    if (x > 2496) {
        generateServerMessage("cannot_move_right");
        return false;
    }
    return {
        x: x,
        y: y,
    };
};
//# sourceMappingURL=move-player.js.map
