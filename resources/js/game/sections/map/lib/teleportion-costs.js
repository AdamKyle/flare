import { removeCommas } from "../../../lib/game/format-number";
export var fetchCost = function (
    xPosition,
    yPosition,
    characterPosition,
    currencies,
) {
    if (
        typeof characterPosition === "undefined" ||
        typeof currencies === "undefined"
    ) {
        return {
            can_afford: true,
            distance: 0,
            cost: 0,
            time_out: 0,
        };
    }
    var distance = calculateDistance(xPosition, yPosition, characterPosition);
    var time = Math.round(distance / 60);
    var cost = time * 1000;
    var canAfford = true;
    if (currencies == null) {
        canAfford = false;
    } else {
        if (cost > removeCommas(currencies.gold)) {
            canAfford = false;
        }
    }
    return {
        can_afford: canAfford,
        distance: distance,
        cost: cost,
        time_out: time,
    };
};
var calculateDistance = function (xPosition, yPosition, characterPosition) {
    var distanceX = Math.pow(xPosition - characterPosition.x, 2);
    var distanceY = Math.pow(yPosition - characterPosition.y, 2);
    var distance = distanceX + distanceY;
    distance = Math.sqrt(distance);
    if (isNaN(distance)) {
        return 0;
    }
    return Math.round(distance);
};
//# sourceMappingURL=teleportion-costs.js.map
