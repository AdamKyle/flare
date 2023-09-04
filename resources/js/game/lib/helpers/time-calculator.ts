import { DateTime } from "luxon";

/**
 * Calculate the time left based on the can_x_again_at
 *
 * @param timeLeft
 */
const calculateTimeLeft = (timeLeft: string): number => {

    const future = DateTime.fromISO(timeLeft);
    const now    = DateTime.now();

    const diff       = future.diff(now, ['seconds']);
    const objectDiff = diff.toObject();

    if (typeof objectDiff.seconds === 'undefined') {
        return 0;
    }

    return parseInt(objectDiff.seconds.toFixed(0));
}

export { calculateTimeLeft };
