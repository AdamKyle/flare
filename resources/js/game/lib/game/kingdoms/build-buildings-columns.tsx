import React, {Fragment} from "react";
import {formatNumber} from "../format-number";
import BuildingDetails from "./building-details";
import clsx from "clsx";
import BuildingInQueueDetails from "./building-in-queue-details";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import {DateTime} from "luxon";

/**
 * Build the columns for the buildings table.
 *
 * @param onClick
 * @param buildingsInQueue
 */
export const buildBuildingsColumns = (onClick: (building: BuildingDetails) => void, buildingsInQueue: BuildingInQueueDetails[]|[]) => {
    return [
        {
            name: 'Name',
            selector: (row: BuildingDetails) => row.name,
            cell: (row: BuildingDetails) =>
                <button onClick={() => onClick(row)}
                        className={clsx({
                            'text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500': !row.is_locked,
                            'text-white underline': row.is_locked
                        })}
                >
                    {row.name}
                </button>
        },
        {
            name: 'Level',
            selector: (row: BuildingDetails) => row.level,
            cell: (row: BuildingDetails) => <span>{row.level}/{row.max_level}</span>
        },
        {
            name: 'Defence',
            selector: (row: BuildingDetails) => row.current_defence,
            cell: (row: BuildingDetails) => formatNumber(row.current_defence),
        },
        {
            name: 'Durability',
            selector: (row: BuildingDetails) => row.current_durability,
            cell: (row: BuildingDetails) => <span>{row.current_durability}/{row.max_durability}</span>
        },
        {
            name: 'Upgrade Time Left',
            minWidth: '300px',
            cell: (row: BuildingDetails) => <Fragment>
                <div className='w-full'>
                    <TimerProgressBar time_remaining={fetchTimeRemaining(row.id, buildingsInQueue)} time_out_label={'Building'} />
                </div>
            </Fragment>,
            omit: buildingsInQueue.length === 0
        }
    ];
}


const fetchTimeRemaining = (buildingId: number, buildingsInQueue: BuildingInQueueDetails[]|[]) => {
    let foundBuilding = buildingsInQueue.filter((building: BuildingInQueueDetails) => {
        return building.building_id === buildingId
    });

    if (foundBuilding.length > 0) {
        const buildingInQueue: BuildingInQueueDetails = foundBuilding[0];

        const start = DateTime.now();
        const end = DateTime.fromISO(buildingInQueue.completed_at);

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