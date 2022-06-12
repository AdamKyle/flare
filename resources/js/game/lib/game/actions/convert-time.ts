import {DateTime} from "luxon";

export const getTimeLeftInSeconds = (time: any): number => {
    if (time !== null) {
        const end   = DateTime.fromISO(time);
        const start = DateTime.now();

        const timeLeft = (end.diff(start, 'seconds')).toObject()

        if (typeof timeLeft === 'undefined') {
            return 0;
        }

        if (typeof timeLeft.seconds === 'undefined') {
            return 0;
        }

        return parseInt(timeLeft.seconds.toFixed(0));
    }

    return 0;
}
