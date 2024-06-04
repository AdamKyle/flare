import { DateTime } from "luxon";
function getRemainingTime(timeLeftInSeconds, timeStartedInSeconds) {
    if (timeStartedInSeconds <= 0) {
        return timeLeftInSeconds;
    }
    var now = DateTime.local();
    var seconds_left = timeLeftInSeconds;
    var currentTime = now.toSeconds();
    var startTime = timeStartedInSeconds;
    var timeElapsedInSeconds = currentTime - startTime;
    return Math.floor(Math.max(seconds_left - timeElapsedInSeconds, 0));
}
export { getRemainingTime };
//# sourceMappingURL=time-left-seconds.js.map
