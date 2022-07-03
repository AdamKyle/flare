import React, {Fragment} from "react";
import {formatNumber} from "../format-number";
import BuildingDetails from "./building-details";
import clsx from "clsx";
import UnitDetails from "./unit-details";
import UnitsInQueue from "./units-in-queue";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import {DateTime} from "luxon";
import CurrentUnitDetails from "./current-unit-details";

/**
 * Build the columns for the units table.
 *
 * @param onClick
 */
export const BuildUnitsColumns = (onClick: (units: UnitDetails) => void, cancelUnitRecruitment: (queueId: number| null) => void, unitsInQueue: UnitsInQueue[]|[], currentUnits: CurrentUnitDetails[]|[]) => {
    return [
        {
            name: 'Name',
            selector: (row: UnitDetails) => row.name,
            cell: (row: UnitDetails) =>
                <button onClick={() => onClick(row)}
                        className={clsx({
                            'text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500': true,
                            'text-white underline': false
                        })}
                >
                    {row.name}
                </button>
        },
        {
            name: 'Recruited From',
            selector: (row: UnitDetails) => row.recruited_from.name,
        },
        {
            name: 'Amount',
            cell: (row: BuildingDetails) => renderAmount(row.id, currentUnits),
        },
        {
            name: 'Attack',
            selector: (row: UnitDetails) => row.attack,
            cell: (row: UnitDetails) => <span>{row.attack}</span>
        },
        {
            name: 'Defence',
            selector: (row: UnitDetails) => row.defence,
            cell: (row: UnitDetails) => formatNumber(row.defence),
        },
        {
            name: 'Upgrade Time Left',
            minWidth: '300px',
            cell: (row: BuildingDetails) => <Fragment>
                <div className='w-full mt-4'>
                    <TimerProgressBar time_remaining={fetchTimeRemaining(row.id, unitsInQueue)} time_out_label={'Training'} />
                    {
                        fetchTimeRemaining(row.id, unitsInQueue) > 0 ?
                            <div className='mt-2 mb-4'>
                                <button className={
                                    'hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 ' +
                                    'disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through ' +
                                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white ' +
                                    'focus-visible:ring-opacity-75'
                                } onClick={() => cancelUnitRecruitment(findUnitInQueue(row.id, unitsInQueue))}>Cancel</button>
                            </div>
                        : null
                    }
                </div>
            </Fragment>,
            omit: unitsInQueue.length === 0
        }
    ];
}

const findUnitInQueue = (unitId: number, unitsInQueue: UnitsInQueue[]|[])  => {
    const foundQueue = unitsInQueue.filter((queue: UnitsInQueue) => {
        return queue.game_unit_id === unitId;
    });

    if (foundQueue.length > 0) {
        const queue: UnitsInQueue = foundQueue[0];

        return queue.id;
    }

    return null;
}

const renderAmount = (unitId: number, currentUnits: CurrentUnitDetails[]|[]) => {
    const foundUnitDetails = currentUnits.filter((unit: CurrentUnitDetails) => {
        return unit.game_unit_id === unitId;
    });

    if (foundUnitDetails.length > 0) {
        let unitDetails: CurrentUnitDetails = foundUnitDetails[0];

        return unitDetails.amount;
    }

    return 0;
}

const fetchTimeRemaining = (unitId: number, unitsInQueue: UnitsInQueue[]|[]) => {
    let foundUnit = unitsInQueue.filter((unit: UnitsInQueue) => {
        return unit.game_unit_id === unitId
    });

    if (foundUnit.length > 0) {
        const unitInQueue: UnitsInQueue = foundUnit[0];

        const start = DateTime.now();
        const end = DateTime.fromISO(unitInQueue.completed_at);

        const difference = end.diff(start, ["seconds"])

        if (typeof difference === 'undefined') {
            return 0;
        }

        if (typeof difference.seconds === 'undefined') {
            return 0;
        }

        return parseInt(difference.seconds.toFixed(0));
    }

    return 0;
}
