/**
 * Fetch the cost of teleporting from current location.
 *
 * @param xPosition
 * @param yPosition
 * @param characterPosition
 * @param currencies
 * @type [{xPosition: number, yPosition: number, characterPosition: {x: number, y: number}, currencies: {gold: number, gold_dust: number, shards: number} | null}]
 * @return  {
 *     can_afford: boolean,
 *     distance: number,
 *     cost: number,
 *     time_out: number,
 * }
 */
export const fetchCost = (xPosition: number, yPosition: number, characterPosition?: {x: number, y: number}, currencies?: {gold: number, gold_dust: number, shards: number}): {
    can_afford: boolean,
    distance: number,
    cost: number,
    time_out: number,
} =>  {

    if (typeof characterPosition === 'undefined' || typeof currencies === 'undefined') {
        return {
            can_afford: true,
            distance: 0,
            cost: 0,
            time_out: 0,
        };
    }

    const distance = calculateDistance(xPosition, yPosition, characterPosition);
    const time     = Math.round(distance / 60);
    const cost     = time * 1000;
    let canAfford  = true;

    if (currencies == null) {
        canAfford = false;
    } else {
        if (cost > currencies.gold) {
            canAfford = false;
        }
    }

    return {
        can_afford: canAfford,
        distance: distance,
        cost: cost,
        time_out: time,
    };
}

const calculateDistance = (xPosition: number, yPosition: number, characterPosition: {x: number, y: number}): number =>  {

    if (xPosition === 0 && yPosition) {
        return 0;
    }

    const distanceX = Math.pow((xPosition - characterPosition.x), 2);
    const distanceY = Math.pow((yPosition - characterPosition.y), 2);

    let distance = distanceX + distanceY;
    distance = Math.sqrt(distance);

    if (isNaN(distance)) {
        return 0;
    }

    return Math.round(distance);
}
