import { DateTime } from "luxon";
export var getTimeLeftInSeconds = function (time) {
    if (time !== null) {
        var end = DateTime.fromISO(time);
        var start = DateTime.now();
        var timeLeft = end.diff(start, "seconds").toObject();
        if (typeof timeLeft === "undefined") {
            return 0;
        }
        if (typeof timeLeft.seconds === "undefined") {
            return 0;
        }
        return parseInt(timeLeft.seconds.toFixed(0));
    }
    return 0;
};
//# sourceMappingURL=convert-time.js.map
