import { DateTime } from "luxon";
var calculateTimeLeft = function (timeLeft) {
    var future = DateTime.fromISO(timeLeft);
    var now = DateTime.now();
    var diff = future.diff(now, ["seconds"]);
    var objectDiff = diff.toObject();
    if (typeof objectDiff.seconds === "undefined") {
        return 0;
    }
    return parseInt(objectDiff.seconds.toFixed(0));
};
export { calculateTimeLeft };
//# sourceMappingURL=time-calculator.js.map
