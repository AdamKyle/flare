import { DateTime } from "luxon";

/**
 * Get the remaining time based on two in puts as a number representing seconds.
 *
 * @param timeLeftInSeconds
 * @param timeStartedInSeconds
 * @returns
 */
function getRemainingTime(
    timeLeftInSeconds: number,
    timeStartedInSeconds: number,
): number {
    if (timeStartedInSeconds <= 0) {
        return timeLeftInSeconds;
    }

    const now = DateTime.local();

    const seconds_left = timeLeftInSeconds;

    const currentTime = now.toSeconds();
    const startTime = timeStartedInSeconds;

    const timeElapsedInSeconds = currentTime - startTime;

    return Math.floor(Math.max(seconds_left - timeElapsedInSeconds, 0));
}

export { getRemainingTime };
